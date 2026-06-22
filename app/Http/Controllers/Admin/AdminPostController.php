<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminPostController extends Controller
{
    /**
     * Lista todas as postagens
     */
    public function index()
    {
        $posts = Post::with(['user' => fn($q) => $q->withoutGlobalScope('active'), 'media'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.posts.index', compact('posts'));
    }

    /**
     * Mostra os detalhes de uma postagem
     */
    public function show($id)
    {
        $post = Post::with(['user' => fn($q) => $q->withoutGlobalScope('active'), 'media'])->findOrFail($id);
        return view('admin.posts.show', compact('post'));
    }

    /**
     * Mostra o formulário de edição
     */
    public function edit($id)
    {
        $post = Post::with(['user' => fn($q) => $q->withoutGlobalScope('active'), 'media'])->findOrFail($id);
        return view('admin.posts.edit', compact('post'));
    }

    /**
     * Atualiza uma postagem
     */
    public function update(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        $validated = $request->validate([
            'description' => 'nullable|string|max:5000',
            'visibility' => ['required', Rule::in(['free', 'subscriber'])],
        ]);

        $post->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Postagem atualizada com sucesso!',
        ]);
    }

    /**
     * Deleta uma mídia específica
     */
    public function deleteMedia($postId, $mediaId)
    {
        $post = Post::findOrFail($postId);
        $media = PostMedia::where('post_id', $postId)->findOrFail($mediaId);

        $media->delete(); // evento do model remove do storage (R2 ou local)

        Log::info('Mídia deletada via admin', [
            'post_id' => $postId,
            'media_id' => $mediaId,
            'file_path' => $media->file_path
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mídia deletada com sucesso!',
        ]);
    }

    /**
     * Deleta todas as mídias de uma postagem
     */
    public function deleteAllMedia($id)
    {
        $post = Post::with('media')->findOrFail($id);

        foreach ($post->media as $media) {
            $media->delete(); // evento do model remove do storage (R2 ou local)
        }

        Log::info('Todas as mídias deletadas via admin', [
            'post_id' => $id,
            'total_deleted' => $post->media->count()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Todas as mídias foram deletadas com sucesso!',
        ]);
    }

    /**
     * Deleta uma postagem completa
     */
    public function destroy($id)
    {
        $post = Post::with('media')->findOrFail($id);

        foreach ($post->media as $media) {
            $media->delete(); // evento do model remove do storage (R2 ou local)
        }

        Log::info('Postagem deletada via admin', [
            'post_id' => $id,
            'media_count' => $post->media->count()
        ]);

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Postagem deletada com sucesso!',
        ]);
    }

}
