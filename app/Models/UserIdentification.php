<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserIdentification extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'document',
        'phone_number',
        'email',
        'cod_ibge',
        'street',
        'number',
        'complement',
        'zip_code',
        'neighborhood',
        'city',
        'state',
    ];

    /**
     * Relacionamento com User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Verifica se todos os dados obrigatórios estão preenchidos
     */
    public function isComplete(): bool
    {
        return !empty($this->name) &&
               !empty($this->document) &&
               !empty($this->phone_number) &&
               !empty($this->cod_ibge) &&
               !empty($this->street) &&
               !empty($this->number) &&
               !empty($this->zip_code) &&
               !empty($this->neighborhood) &&
               !empty($this->city) &&
               !empty($this->state);
    }

    /**
     * Retorna os dados no formato esperado (com address como objeto)
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'document' => $this->document,
            'phoneNumber' => $this->phone_number,
            'address' => [
                'codIbge' => $this->cod_ibge,
                'street' => $this->street,
                'number' => $this->number,
                'complement' => $this->complement ?? '',
                'zipCode' => $this->zip_code,
                'neighborhood' => $this->neighborhood,
                'city' => $this->city,
                'state' => $this->state,
            ],
        ];
    }
}
