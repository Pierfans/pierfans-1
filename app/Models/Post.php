<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\PostPurchase;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'description',
        'visibility',
        'price',
        'deleted_by_user_at',
        'featured_on_login',
        'featured_on_dashboard',
    ];

    protected $casts = [
        'deleted_by_user_at' => 'datetime',
        'price'              => 'decimal:2',
    ];

    /**
     * The "booted" method of the model.
     * Adiciona scope global para não mostrar posts deletados pelo usuário
     */
    protected static function booted(): void
    {
        static::addGlobalScope('notDeletedByUser', function ($builder) {
            $builder->whereNull('deleted_by_user_at');
        });
    }

    /**
     * Relacionamento com User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com PostMedia
     */
    public function media(): HasMany
    {
        return $this->hasMany(PostMedia::class)->orderBy('order');
    }

    /**
     * Relacionamento com PostLikes
     */
    public function likes(): HasMany
    {
        return $this->hasMany(PostLike::class);
    }

    /**
     * Relacionamento com Comments
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id')->orderBy('created_at', 'desc');
    }

    /**
     * Relacionamento com Reports
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Verifica se o usuário curtiu a postagem
     */
    public function isLikedBy($userId): bool
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }

    /**
     * Verifica se o usuário é criador aprovado
     */
    public static function canCreatePost($userId): bool
    {
        $user = User::find($userId);
        return $user && $user->creator_status === 'approved';
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(PostPurchase::class);
    }

    public function isPurchasedBy(int $userId): bool
    {
        return $this->purchases()->where('user_id', $userId)->exists();
    }

    /**
     * Quem pode acessar o ARQUIVO desta postagem. Usado pela rota que entrega a
     * mídia do R2 (post-media.stream): sem isso qualquer usuário logado baixa
     * conteúdo de assinante e de PPV só chutando o id da mídia.
     *
     * Não é a mesma regra do post-card: lá o admin não fura paywall no feed, mas
     * aqui ele precisa passar, senão a tela /admin/posts/{id} não carrega a mídia.
     */
    public function canBeViewedBy(?User $user): bool
    {
        if ($this->visibility === 'free') {
            return true;
        }
        if (!$user) {
            return false;
        }
        if ($this->user_id === $user->id || $user->is_admin) {
            return true;
        }

        return $this->visibility === 'paid'
            ? $this->isPurchasedBy($user->id)
            : $user->hasActiveSubscription($this->user_id);
    }

    /**
     * Conteúdo Único (paid) que já tem comprador não pode ser excluído/desabilitado:
     * o soft-delete tiraria o acesso de quem pagou e o hard-delete cascatearia o
     * registro em post_purchases. Único caminho é remoção manual no servidor (admin).
     */
    public function isPurchasedUnique(): bool
    {
        return $this->visibility === 'paid' && $this->purchases()->exists();
    }
}
