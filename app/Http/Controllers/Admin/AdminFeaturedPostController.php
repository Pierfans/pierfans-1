<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class AdminFeaturedPostController extends Controller
{
    public function index()
    {
        $posts = Post::with(['user', 'media'])
            ->where('visibility', 'free')
            ->whereHas('user', function ($q) {
                $q->where('creator_status', 'approved');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.featured-posts.index', compact('posts'));
    }

    public function toggleLogin(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        $post->featured_on_login = !$post->featured_on_login;
        $post->save();

        return response()->json([
            'success' => true,
            'message' => $post->featured_on_login
                ? 'Post adicionado à tela de login com sucesso!'
                : 'Post removido da tela de login com sucesso!',
            'featured_on_login' => $post->featured_on_login,
        ]);
    }

    public function toggleDashboard(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        $post->featured_on_dashboard = !$post->featured_on_dashboard;
        $post->save();

        return response()->json([
            'success' => true,
            'message' => $post->featured_on_dashboard
                ? 'Post adicionado ao dashboard com sucesso!'
                : 'Post removido do dashboard com sucesso!',
            'featured_on_dashboard' => $post->featured_on_dashboard,
        ]);
    }
}
