<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    protected $fillable = [
        'post_id',
        'user_id',
        'parent_id',
        'content',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Autor do comentário, INCLUSIVE se a conta dele estiver desativada.
     *
     * O model User tem o global scope 'active', que esconde criador desativado das listagens.
     * Sem tirar o scope aqui, o autor vira null e a tela de comentários quebra inteira (não só
     * o comentário dele): em 26/07/2026 isso derrubou `getComments` de 22 posts, porque 38 dos
     * 92 comentários da plataforma são de contas desativadas.
     *
     * O scope continua valendo em `Post::user()` de propósito — lá ele é o que tira o post do
     * criador desativado do feed (`whereHas('user')` em PostController::index).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withoutGlobalScope('active');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CommentLike::class);
    }

    public function isLikedBy($userId): bool
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }
}
