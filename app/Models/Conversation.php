<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'creator_id',
        'subscriber_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    /**
     * Relacionamento com o criador
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Relacionamento com o assinante
     */
    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subscriber_id');
    }

    /**
     * Relacionamento com as mensagens
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Retorna o outro participante da conversa (não o usuário atual)
     */
    public function getOtherParticipant($currentUserId)
    {
        if ($this->creator_id == $currentUserId) {
            return $this->subscriber;
        }
        return $this->creator;
    }

    /**
     * Retorna a última mensagem da conversa
     */
    public function lastMessage()
    {
        return $this->messages()->latest()->first();
    }

    /**
     * Conta mensagens não lidas para um usuário específico
     */
    public function unreadCount($userId)
    {
        return $this->messages()
            ->where('user_id', '!=', $userId)
            ->whereNull('read_at')
            ->count();
    }
}
