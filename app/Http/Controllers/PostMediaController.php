<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;
use App\Models\Post;
use App\Models\PostMedia;
use Aws\S3\S3Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PostMediaController extends Controller
{
    private const ALLOWED_IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'webp'];
    private const ALLOWED_VIDEO_TYPES = ['mp4', 'mov', 'webm'];
    private const PRESIGNED_UPLOAD_EXPIRY_MINUTES = 60;
    private const PRESIGNED_VIEW_EXPIRY_MINUTES = 60;
    private const MAX_FILE_SIZE_MB = 5120; // 5GB for videos

    /**
     * Generate a presigned PUT URL for direct upload to R2.
     * Browser will upload the file to this URL; Laravel never receives the file.
     */
    public function requestUploadUrl(Request $request): JsonResponse
    {
        if (!PlatformSetting::isUseR2Upload()) {
            return response()->json([
                'success' => false,
                'message' => 'Upload para R2 está desativado. As mídias são salvas localmente.',
            ], 503);
        }

        $validated = $request->validate([
            'filename' => ['required', 'string', 'max:255'],
            'content_type' => ['required', 'string', 'max:100'],
            'post_id' => ['nullable', 'exists:posts,id'],
            'size' => ['nullable', 'integer', 'min:0', 'max:' . (self::MAX_FILE_SIZE_MB * 1024 * 1024)],
        ]);

        $extension = strtolower(pathinfo($validated['filename'], PATHINFO_EXTENSION));
        $allowed = array_merge(self::ALLOWED_IMAGE_TYPES, self::ALLOWED_VIDEO_TYPES);
        if (!in_array($extension, $allowed, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de arquivo não permitido. Use: ' . implode(', ', $allowed),
            ], 422);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Não autorizado.'], 401);
        }

        if ($user->creator_status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas criadores aprovados podem enviar mídias.',
            ], 403);
        }

        if (isset($validated['post_id'])) {
            $post = Post::find($validated['post_id']);
            if (!$post || $post->user_id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Post não encontrado ou sem permissão.'], 403);
            }
        }

        $key = $this->buildObjectKey($user->id, $validated['filename']);

        try {
            $client = $this->getR2Client();
            $bucket = config('filesystems.disks.r2.bucket');
            $expires = '+' . self::PRESIGNED_UPLOAD_EXPIRY_MINUTES . ' minutes';

            $command = $client->getCommand('PutObject', [
                'Bucket' => $bucket,
                'Key' => $key,
                'ContentType' => $request->input('content_type'),
            ]);
            $presignedRequest = $client->createPresignedRequest($command, $expires);
            $uploadUrl = (string) $presignedRequest->getUri();

            return response()->json([
                'success' => true,
                'upload_url' => $uploadUrl,
                'key' => $key,
                'expires_in_seconds' => self::PRESIGNED_UPLOAD_EXPIRY_MINUTES * 60,
            ]);
        } catch (\Throwable $e) {
            Log::error('R2 presigned URL error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar URL de upload.',
            ], 500);
        }
    }

    /**
     * Save media metadata after the client has uploaded the file to R2.
     * Laravel only stores the key; it never receives the file.
     */
    public function confirmUpload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:512'],
            'post_id' => ['required', 'exists:posts,id'],
            'filename' => ['required', 'string', 'max:255'],
            'size' => ['nullable', 'integer', 'min:0'],
            'type' => ['required', Rule::in(['image', 'video'])],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Não autorizado.'], 401);
        }

        $post = Post::findOrFail($validated['post_id']);
        if ($post->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Sem permissão para este post.'], 403);
        }

        $key = $validated['key'];
        if (!Str::startsWith($key, 'uploads/posts/' . $user->id . '/')) {
            return response()->json(['success' => false, 'message' => 'Chave de arquivo inválida.'], 422);
        }

        $maxOrder = (int) $post->media()->max('order');
        $order = isset($validated['order']) ? (int) $validated['order'] : $maxOrder + 1;

        $media = PostMedia::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'file_path' => $key,
            'file_type' => $validated['type'],
            'order' => $order,
            'size' => $validated['size'] ?? null,
        ]);

        // Dispara conversão automática se for .mov
        $ext = strtolower(pathinfo($media->file_path, PATHINFO_EXTENSION));
        if (in_array($ext, ['mov'])) {
            \App\Jobs\ConvertVideoToMp4::dispatch($media->id)->delay(now()->addSeconds(5));
            Log::info('ConvertVideoToMp4: job disparado', ['mediaId' => $media->id]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Mídia registrada.',
            'media' => [
                'id' => $media->id,
                'key' => $media->file_path,
                'type' => $media->file_type,
                'order' => $media->order,
            ],
        ], 201);
    }

    /**
     * Return a temporary presigned GET URL so the client can view a private R2 object.
     */
    public function stream(int $id): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $media = PostMedia::with('post')->findOrFail($id);

        if (request()->wantsJson()) {
            try {
                $url = $this->getPresignedViewUrl($media->file_path);
                return response()->json(['success' => true, 'url' => $url]);
            } catch (\Throwable $e) {
                Log::error('R2 presigned view URL error', ['id' => $id, 'error' => $e->getMessage()]);
                return response()->json(['success' => false, 'message' => 'Erro ao gerar URL.'], 500);
            }
        }

        try {
            $url = $this->getPresignedViewUrl($media->file_path);
            return redirect()->away($url);
        } catch (\Throwable $e) {
            Log::error('R2 presigned view URL error', ['id' => $id, 'error' => $e->getMessage()]);
            abort(502, 'Arquivo temporariamente indisponível.');
        }
    }

    private function buildObjectKey(int $userId, string $originalFilename): string
    {
        $ext = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        $safeName = Str::random(24) . '_' . time() . '.' . $ext;
        return 'uploads/posts/' . $userId . '/' . $safeName;
    }

    private function getR2Client(): S3Client
    {
        $config = config('filesystems.disks.r2');
        return new S3Client([
            'version' => 'latest',
            'region' => $config['region'],
            'endpoint' => $config['endpoint'],
            'credentials' => [
                'key' => $config['key'],
                'secret' => $config['secret'],
            ],
            'use_path_style_endpoint' => $config['use_path_style_endpoint'] ?? false,
        ]);
    }

    private function getPresignedViewUrl(string $key): string
    {
        $client = $this->getR2Client();
        $bucket = config('filesystems.disks.r2.bucket');
        $command = $client->getCommand('GetObject', [
            'Bucket' => $bucket,
            'Key' => $key,
        ]);
        $request = $client->createPresignedRequest($command, '+' . self::PRESIGNED_VIEW_EXPIRY_MINUTES . ' minutes');
        return (string) $request->getUri();
    }
}
