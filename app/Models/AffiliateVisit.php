<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateVisit extends Model
{
    protected $fillable = [
        'affiliate_id',
        'slug',
        'is_external',
        'referer',
        'ip_address',
        'user_agent',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'gclid',
        'fbclid',
    ];

    /**
     * Relacionamento com o afiliado (User)
     */
    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'affiliate_id');
    }
}
