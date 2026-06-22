<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccount extends Model
{
    protected $fillable = [
        'user_id',
        'bank_name',
        'bank_code',
        'account_type',
        'agency',
        'account_number',
        'pix_key_type',
        'pix_key',
        'is_primary',
    ];

    /**
     * Relacionamento com User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para contas primárias
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Define como conta primária
     */
    public function setAsPrimary(): void
    {
        // Remove primary de outras contas do mesmo usuário
        BankAccount::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        $this->update(['is_primary' => true]);
    }

    /**
     * Formata a exibição da conta
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->bank_name} - " . ucfirst($this->pix_key_type);
    }
}
