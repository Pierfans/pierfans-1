<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostLike;
use App\Models\Comment;
use App\Models\CommentLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostInteractionController extends Controller
{
    /**
     * Curtir/Descurtir postagem
     */
    public function toggleLike(Request $request, $postId)
    {
        $post = Post::findOrFail($postId);
        $userId = Auth::id();

        $like = PostLike::where('post_id', $postId)
            ->where('user_id', $userId)
            ->first();

        if ($like) {
            $like->delete();
            $liked = false;
        } else {
            PostLike::create([
                'post_id' => $postId,
                'user_id' => $userId,
            ]);
            $liked = true;
        }

        $likesCount = $post->likes()->count();

        return response()->json([
            'success' => true,
            'liked' => $liked,
            'likes_count' => $likesCount,
        ]);
    }

    /**
     * Listar comentários de uma postagem
     */
    public function getComments($postId)
    {
        $post = Post::with([
            'comments.user', 
            'comments.replies.user', 
            'comments.likes',
            'comments.replies.likes'
        ])->findOrFail($postId);

        $comments = $post->comments->map(function ($comment) {
            return [
                'id' => $comment->id,
                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'profile_photo' => $comment->user->profile_photo_url,
                ],
                'content' => $comment->content,
                'created_at' => $comment->created_at->diffForHumans(),
                'likes_count' => $comment->likes->count(),
                'is_liked' => $comment->isLikedBy(Auth::id()),
                'replies' => $comment->replies->map(function ($reply) {
                    return [
                        'id' => $reply->id,
                        'user' => [
                            'id' => $reply->user->id,
                            'name' => $reply->user->name,
                            'profile_photo' => $reply->user->profile_photo_url,
                        ],
                        'content' => $reply->content,
                        'created_at' => $reply->created_at->diffForHumans(),
                        'likes_count' => $reply->likes->count(),
                        'is_liked' => $reply->isLikedBy(Auth::id()),
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'comments' => $comments,
        ]);
    }

    /**
     * Criar comentário
     */
    public function createComment(Request $request, $postId)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $comment = Comment::create([
            'post_id' => $postId,
            'user_id' => Auth::id(),
            'parent_id' => $request->parent_id,
            'content' => $request->content,
        ]);

        $comment->load('user', 'likes');

        return response()->json([
            'success' => true,
            'comment' => [
                'id' => $comment->id,
                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'profile_photo' => $comment->user->profile_photo_url,
                ],
                'content' => $comment->content,
                'created_at' => $comment->created_at->diffForHumans(),
                'likes_count' => 0,
                'is_liked' => false,
                'replies' => [],
            ],
        ]);
    }

    /**
     * Curtir/Descurtir comentário
     */
    public function toggleCommentLike(Request $request, $commentId)
    {
        $comment = Comment::findOrFail($commentId);
        $userId = Auth::id();

        $like = CommentLike::where('comment_id', $commentId)
            ->where('user_id', $userId)
            ->first();

        if ($like) {
            $like->delete();
            $liked = false;
        } else {
            CommentLike::create([
                'comment_id' => $commentId,
                'user_id' => $userId,
            ]);
            $liked = true;
        }

        $likesCount = $comment->likes()->count();

        return response()->json([
            'success' => true,
            'liked' => $liked,
            'likes_count' => $likesCount,
        ]);
    }
}
