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
    public function index(Request $request)
    {
        // whereHas('user') aplica o scope global 'active' do User: posts de criadora
        // desativada (is_active=false) somem desta tela, como deve ser.
        $posts = Post::whereHas('user')
            ->with(['user' => fn($q) => $q->withoutGlobalScope('active'), 'media'])
            ->when($request->filled('creator_id'), fn($q) => $q->where('user_id', $request->creator_id))
            ->when($request->filled('visibility'), fn($q) => $q->where('visibility', $request->visibility))
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        // Só criadoras ativas (scope default) — desativadas não têm posts visíveis aqui.
        $creators = \App\Models\User::where('creator_status', 'approved')
            ->whereNotNull('username')
            ->orderBy('name')
            ->get(['id', 'name', 'username']);

        return view('admin.posts.index', compact('posts', 'creators'));
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

        // Conteúdo Único com compra: hard-delete cascatearia o registro em post_purchases.
        // Bloqueia o botão; caso gritante = remoção manual no servidor (como combinado).
        if ($post->isPurchasedUnique()) {
            return response()->json([
                'success' => false,
                'message' => 'Este Conteúdo Único tem compra registrada. Excluir apagaria o registro da compra (post_purchases). Remova manualmente no servidor se for realmente necessário.',
            ], 403);
        }

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
