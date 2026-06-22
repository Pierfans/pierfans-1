<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminTrashController extends Controller
{
    /**
     * Lista todas as postagens na lixeira (deletadas pelos usuários)
     */
    public function index()
    {
        $posts = Post::withoutGlobalScope('notDeletedByUser')
            ->whereNotNull('deleted_by_user_at')
            ->with(['user', 'media'])
            ->orderBy('deleted_by_user_at', 'desc')
            ->paginate(20);

        return view('admin.trash.index', compact('posts'));
    }

    /**
     * Mostra os detalhes de uma postagem na lixeira
     */
    public function show($id)
    {
        $post = Post::withoutGlobalScope('notDeletedByUser')
            ->whereNotNull('deleted_by_user_at')
            ->with(['user', 'media'])
            ->findOrFail($id);

        return view('admin.trash.show', compact('post'));
    }

    /**
     * Restaura uma postagem da lixeira
     */
    public function restore($id)
    {
        $post = Post::withoutGlobalScope('notDeletedByUser')
            ->whereNotNull('deleted_by_user_at')
            ->findOrFail($id);

        $post->update(['deleted_by_user_at' => null]);

        Log::info('Postagem restaurada pelo admin', [
            'post_id' => $post->id,
            'admin_id' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Postagem restaurada com sucesso!',
        ]);
    }

    /**
     * Deleta permanentemente uma postagem da lixeira
     */
    public function destroy($id)
    {
        $post = Post::withoutGlobalScope('notDeletedByUser')
            ->whereNotNull('deleted_by_user_at')
            ->with('media')
            ->findOrFail($id);

        foreach ($post->media as $media) {
            $media->delete(); // evento do model remove do storage (R2 ou local)
        }

        Log::info('Postagem deletada permanentemente pelo admin', [
            'post_id' => $post->id,
            'admin_id' => auth()->id(),
            'media_count' => $post->media->count()
        ]);

        $post->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Postagem deletada permanentemente!',
        ]);
    }

}
