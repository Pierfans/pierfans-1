<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'message_type',
        'content',
        'file_path',
        'file_disk',
        'price',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'price'   => 'decimal:2',
    ];

    /**
     * Relacionamento com a conversa
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Relacionamento com o usuário que enviou
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Compras desta mensagem
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(MessagePurchase::class);
    }

    /**
     * Mensagem com preço: nasce trancada e só abre pra quem comprar.
     */
    public function isPaid(): bool
    {
        return $this->price !== null && (float) $this->price > 0;
    }

    /**
     * Quem enxerga o conteúdo. Quem mandou sempre vê o que mandou, e admin vê tudo pra
     * conseguir moderar denúncia. O resto só depois de comprar.
     */
    public function isUnlockedFor(?User $user): bool
    {
        if (!$this->isPaid()) {
            return true;
        }

        if (!$user) {
            return false;
        }

        if ($user->id === $this->user_id || $user->is_admin) {
            return true;
        }

        return $this->purchases()->where('user_id', $user->id)->exists();
    }

    /**
     * O que o chat manda pro navegador.
     *
     * Mensagem trancada não leva file_path nem URL: quem não comprou não precisa nem saber
     * o caminho do arquivo. A legenda continua visível de propósito — é ela que vende.
     */
    public function toChatPayload(?User $viewer): array
    {
        $aberta = $this->isUnlockedFor($viewer);

        return [
            'id'           => $this->id,
            'user_id'      => $this->user_id,
            'message_type' => $this->message_type,
            'content'      => $this->content,
            'created_at'   => $this->created_at,
            'read_at'      => $this->read_at,
            'user'         => [
                'id'   => $this->user->id ?? null,
                'name' => $this->user->name ?? null,
            ],
            'is_paid'   => $this->isPaid(),
            'price'     => $this->isPaid() ? (float) $this->price : null,
            'unlocked'  => $aberta,
            'media_url' => ($aberta && $this->file_path) ? route('chat.media', $this->id) : null,
        ];
    }

    /**
     * Marca a mensagem como lida
     */
    public function markAsRead()
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }
}
