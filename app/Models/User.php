<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'slug',
        'username',
        'description',
        'cover_photo',
        'profile_photo',
        'social_media',
        'creator_status',
        'creator_full_name',
        'creator_cpf',
        'creator_birth_date',
        'creator_phone',
        'creator_zipcode',
        'creator_address',
        'creator_address_number',
        'creator_address_complement',
        'creator_neighborhood',
        'creator_city',
        'creator_state',
        'creator_bank_name',
        'creator_bank_agency',
        'creator_bank_account',
        'creator_bank_account_type',
        'creator_pix_key',
        'creator_document_front',
        'creator_document_back',
        'creator_selfie',
        'creator_submitted_at',
        'featured_in_dashboard',
        'featured_in_top_creators',
        'is_admin',
        'is_active',
        'registration_url',
        'creator_onboarding',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'creator_birth_date' => 'date',
            'creator_submitted_at' => 'datetime',
            'social_media' => 'array',
            'featured_in_dashboard' => 'boolean',
            'featured_in_top_creators' => 'boolean',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'creator_onboarding' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('active', function ($query) {
            $query->where(function ($q) {
                $q->where('is_active', true)
                  ->orWhere('creator_status', 'none');
            });
        });
    }

    /**
     * Relacionamento com SubscriptionPlan
     */
    public function subscriptionPlans()
    {
        return $this->hasMany(\App\Models\SubscriptionPlan::class);
    }

    /**
     * Pelo menos um plano de assinatura ativo (necessário para postagens "Somente assinantes").
     */
    public function hasActiveSubscriptionPlans(): bool
    {
        return $this->subscriptionPlans()->where('is_active', true)->exists();
    }

    /**
     * Conversas onde o usuário é criador
     */
    public function creatorConversations()
    {
        return $this->hasMany(\App\Models\Conversation::class, 'creator_id');
    }

    /**
     * Conversas onde o usuário é assinante
     */
    public function subscriberConversations()
    {
        return $this->hasMany(\App\Models\Conversation::class, 'subscriber_id');
    }

    /**
     * Todas as conversas do usuário (como criador ou assinante)
     */
    public function conversations()
    {
        return \App\Models\Conversation::where(function($query) {
            $query->where('creator_id', $this->id)
                  ->orWhere('subscriber_id', $this->id);
        });
    }

    /**
     * Mensagens enviadas pelo usuário
     */
    public function messages()
    {
        return $this->hasMany(\App\Models\Message::class);
    }

    /**
     * Relacionamento com UserIdentification
     */
    public function identification()
    {
        return $this->hasOne(\App\Models\UserIdentification::class);
    }

    /**
     * Verifica se o usuário tem dados de identificação completos
     */
    public function hasCompleteIdentification(): bool
    {
        return $this->identification && $this->identification->isComplete();
    }

    /**
     * Assinaturas onde o usuário é o assinante
     */
    public function subscriptions()
    {
        return $this->hasMany(\App\Models\Subscription::class, 'user_id');
    }

    /**
     * Assinaturas onde o usuário é o criador
     */
    public function creatorSubscriptions()
    {
        return $this->hasMany(\App\Models\Subscription::class, 'creator_id');
    }

    /**
     * Verifica se o usuário tem assinatura ativa com um criador
     */
    public function hasActiveSubscription($creatorId): bool
    {
        return $this->subscriptions()
            ->where('creator_id', $creatorId)
            ->where('is_active', true)
            ->where('end_date', '>=', now()->toDateString())
            ->exists();
    }

    /**
     * Obtém a assinatura ativa com um criador
     */
    public function getActiveSubscription($creatorId)
    {
        return $this->subscriptions()
            ->where('creator_id', $creatorId)
            ->where('is_active', true)
            ->where('end_date', '>=', now()->toDateString())
            ->first();
    }

    /**
     * Relacionamento com BankAccount
     */
    public function bankAccounts()
    {
        return $this->hasMany(\App\Models\BankAccount::class);
    }

    /**
     * Relacionamento com Withdrawal
     */
    public function withdrawals()
    {
        return $this->hasMany(\App\Models\Withdrawal::class);
    }

    /**
     * Relacionamento com ManualCredit
     */
    public function manualCredits()
    {
        return $this->hasMany(\App\Models\ManualCredit::class);
    }

    /**
     * Relacionamento com Wallet
     */
    public function wallet()
    {
        return $this->hasOne(\App\Models\Wallet::class);
    }

    /**
     * Obtém ou cria a carteira do usuário
     */
    public function getOrCreateWallet(): \App\Models\Wallet
    {
        if (!$this->relationLoaded('wallet')) {
            $this->load('wallet');
        }
        
        if (!$this->wallet) {
            $this->wallet = \App\Models\Wallet::create([
                'user_id' => $this->id,
                'balance' => 0.00,
            ]);
        }
        
        return $this->wallet;
    }

    /**
     * Obtém o saldo da carteira do usuário
     */
    public function getWalletBalance(): float
    {
        $wallet = $this->wallet;
        return $wallet ? (float) $wallet->balance : 0.00;
    }

    /**
     * Obtém a conta bancária primária
     */
    public function primaryBankAccount()
    {
        return $this->bankAccounts()->where('is_primary', true)->first();
    }

    /**
     * Calcula o saldo liberado para saque
     * Baseado em assinaturas reais que já completaram o prazo de liberação
     */
    public function getAvailableBalance(): float
    {
        $pixReleaseDays = \App\Models\PlatformSetting::getPixReleaseDays();
        $cardReleaseDays = \App\Models\PlatformSetting::getCardReleaseDays();
        
        // Data limite: hoje menos os dias de bloqueio
        // Se dias = 0, usa now() para liberação imediata
        // Se dias > 0, usa endOfDay() para considerar todo o dia
        $pixReleaseDate = $pixReleaseDays == 0 
            ? now() 
            : now()->subDays($pixReleaseDays)->endOfDay();
        $cardReleaseDate = $cardReleaseDays == 0 
            ? now() 
            : now()->subDays($cardReleaseDays)->endOfDay();
        
        // Soma o valor do criador de assinaturas já liberadas
        $releasedAmount = $this->creatorSubscriptions()
            ->where(function ($query) use ($pixReleaseDate, $cardReleaseDate) {
                // PIX: liberado se created_at + dias <= hoje
                $query->where(function ($q) use ($pixReleaseDate) {
                    $q->where('payment_method', 'pix')
                      ->where('created_at', '<=', $pixReleaseDate);
                })
                // Cartão: liberado se created_at + dias <= hoje
                ->orWhere(function ($q) use ($cardReleaseDate) {
                    $q->where('payment_method', 'card')
                      ->where('created_at', '<=', $cardReleaseDate);
                });
            })
            ->sum('creator_amount');
        
        // Subtrai saques pendentes e transferidos
        $pendingWithdrawals = $this->withdrawals()
            ->where('type', 'creator')
            ->whereIn('status', ['pending', 'transferred'])
            ->sum('amount');
        
        // Adiciona créditos manuais do tipo creator
        $manualCredits = $this->manualCredits()
            ->where('type', 'creator')
            ->sum('amount');
        
        return max(0, (float) $releasedAmount - (float) $pendingWithdrawals + (float) $manualCredits);
    }

    /**
     * Calcula o saldo a liberar
     * Pagamentos que ainda não completaram o prazo configurado
     * Inclui TODAS as vendas que ainda não foram liberadas, incluindo as do dia atual
     */
    public function getPendingBalance(): float
    {
        $pixReleaseDays = \App\Models\PlatformSetting::getPixReleaseDays();
        $cardReleaseDays = \App\Models\PlatformSetting::getCardReleaseDays();
        
        // Se dias = 0, não há saldo a liberar (tudo é liberado imediatamente)
        if ($pixReleaseDays == 0 && $cardReleaseDays == 0) {
            return 0.00;
        }
        
        // Data limite: hoje menos os dias de bloqueio
        // Se dias = 0, usa now() para não considerar nada como pendente
        // Se dias > 0, usa endOfDay() para considerar todo o dia
        $pixReleaseDate = $pixReleaseDays == 0 
            ? now()->addYear() // Data futura para não considerar nada como pendente
            : now()->subDays($pixReleaseDays)->endOfDay();
        $cardReleaseDate = $cardReleaseDays == 0 
            ? now()->addYear() // Data futura para não considerar nada como pendente
            : now()->subDays($cardReleaseDays)->endOfDay();
        
        // Soma o valor do criador de assinaturas ainda não liberadas
        // Isso inclui vendas do dia atual que ainda estão bloqueadas
        $pendingAmount = $this->creatorSubscriptions()
            ->where(function ($query) use ($pixReleaseDate, $cardReleaseDate) {
                // PIX: não liberado se created_at + dias > hoje
                $query->where(function ($q) use ($pixReleaseDate) {
                    $q->where('payment_method', 'pix')
                      ->where('created_at', '>', $pixReleaseDate);
                })
                // Cartão: não liberado se created_at + dias > hoje
                ->orWhere(function ($q) use ($cardReleaseDate) {
                    $q->where('payment_method', 'card')
                      ->where('created_at', '>', $cardReleaseDate);
                });
            })
            ->sum('creator_amount');
        
        return (float) $pendingAmount;
    }

    /**
     * Calcula o faturamento do dia atual
     * Soma todas as vendas (assinaturas) realizadas hoje
     */
    public function getTodayRevenue(): float
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        
        // Soma o valor total (total_amount) de todas as assinaturas criadas hoje
        $todayRevenue = $this->creatorSubscriptions()
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->sum('total_amount');
        
        return (float) $todayRevenue;
    }

    /**
     * Obtém o valor total de saques pendentes
     */
    public function getPendingWithdrawalAmount(): float
    {
        return (float) $this->withdrawals()
            ->where('status', 'pending')
            ->sum('amount');
    }

    /**
     * Relacionamento com Referral (como usuário indicado)
     */
    public function referral()
    {
        return $this->hasOne(\App\Models\Referral::class, 'referred_user_id');
    }

    /**
     * Relacionamento com Referrals (como usuário indicador)
     */
    public function referrals()
    {
        return $this->hasMany(\App\Models\Referral::class, 'referrer_user_id');
    }

    /**
     * Assinaturas que geraram comissão para este afiliado
     * Inclui:
     * 1. Comissões quando o indicado assina planos (referrer_amount)
     * 2. Comissões quando o criador indicado vende planos (creator_affiliate_amount)
     */
    public function affiliateCommissions()
    {
        // Busca IDs de usuários que foram indicados por este afiliado
        $referredUserIds = $this->referrals()->pluck('referred_user_id');
        
        if ($referredUserIds->isEmpty()) {
            // Retorna query vazia se não houver indicações
            return \App\Models\Subscription::whereRaw('1 = 0');
        }
        
        // Busca subscriptions que geraram comissão para este afiliado:
        // 1. Quando o indicado assina (referrer_amount > 0 e user_id está na lista de indicados)
        // 2. Quando o criador indicado vende (creator_affiliate_amount > 0 e creator_id está na lista de indicados)
        return \App\Models\Subscription::where(function($query) use ($referredUserIds) {
            // Comissões quando o indicado assina
            $query->where(function($q) use ($referredUserIds) {
                $q->whereIn('user_id', $referredUserIds)
                  ->where('referrer_amount', '>', 0)
                  ->whereHas('user', function ($subQuery) {
                      $subQuery->whereHas('referral', function ($refQuery) {
                          $refQuery->where('referrer_user_id', $this->id);
                      });
                  });
            })
            // Comissões quando o criador indicado vende
            ->orWhere(function($q) use ($referredUserIds) {
                $q->whereIn('creator_id', $referredUserIds)
                  ->where('creator_affiliate_amount', '>', 0)
                  ->where('creator_affiliate_user_id', $this->id);
            });
        });
    }

    /**
     * Calcula o saldo liberado para saque do afiliado
     * Reutiliza a mesma lógica do criador, mas aplicada às comissões de afiliado
     */
    public function getAffiliateAvailableBalance(): float
    {
        $pixReleaseDays = \App\Models\PlatformSetting::getPixReleaseDays();
        $cardReleaseDays = \App\Models\PlatformSetting::getCardReleaseDays();
        
        // Data limite: hoje menos os dias de bloqueio
        $pixReleaseDate = $pixReleaseDays == 0 
            ? now() 
            : now()->subDays($pixReleaseDays)->endOfDay();
        $cardReleaseDate = $cardReleaseDays == 0 
            ? now() 
            : now()->subDays($cardReleaseDays)->endOfDay();
        
        // Soma o valor de comissões já liberadas
        // Inclui tanto referrer_amount (quando indicado assina) quanto creator_affiliate_amount (quando criador indicado vende)
        $releasedCommissions = $this->affiliateCommissions()
            ->where(function ($query) use ($pixReleaseDate, $cardReleaseDate) {
                // PIX: liberado se created_at + dias <= hoje
                $query->where(function ($q) use ($pixReleaseDate) {
                    $q->where('payment_method', 'pix')
                      ->where('created_at', '<=', $pixReleaseDate);
                })
                // Cartão: liberado se created_at + dias <= hoje
                ->orWhere(function ($q) use ($cardReleaseDate) {
                    $q->where('payment_method', 'card')
                      ->where('created_at', '<=', $cardReleaseDate);
                });
            })
            ->get();
        
        // Soma referrer_amount (comissões quando indicado assina) + creator_affiliate_amount (comissões quando criador indicado vende)
        $releasedAmount = $releasedCommissions->sum(function($subscription) {
            return (float) $subscription->referrer_amount + (float) $subscription->creator_affiliate_amount;
        });
        
        // Subtrai saques pendentes e transferidos do afiliado
        $pendingWithdrawals = $this->affiliateWithdrawals()
            ->whereIn('status', ['pending', 'transferred'])
            ->sum('amount');
        
        // Adiciona créditos manuais do tipo affiliate
        $manualCredits = $this->manualCredits()
            ->where('type', 'affiliate')
            ->sum('amount');
        
        return max(0, (float) $releasedAmount - (float) $pendingWithdrawals + (float) $manualCredits);
    }

    /**
     * Calcula o saldo a liberar do afiliado
     * Reutiliza a mesma lógica do criador
     */
    public function getAffiliatePendingBalance(): float
    {
        $pixReleaseDays = \App\Models\PlatformSetting::getPixReleaseDays();
        $cardReleaseDays = \App\Models\PlatformSetting::getCardReleaseDays();
        
        // Se dias = 0, não há saldo a liberar
        if ($pixReleaseDays == 0 && $cardReleaseDays == 0) {
            return 0.00;
        }
        
        // Data limite: hoje menos os dias de bloqueio
        $pixReleaseDate = $pixReleaseDays == 0 
            ? now()->addYear() // Data futura para não considerar nada como pendente
            : now()->subDays($pixReleaseDays)->endOfDay();
        $cardReleaseDate = $cardReleaseDays == 0 
            ? now()->addYear() // Data futura para não considerar nada como pendente
            : now()->subDays($cardReleaseDays)->endOfDay();
        
        // Soma o valor de comissões ainda não liberadas
        // Inclui tanto referrer_amount (quando indicado assina) quanto creator_affiliate_amount (quando criador indicado vende)
        $pendingCommissions = $this->affiliateCommissions()
            ->where(function ($query) use ($pixReleaseDate, $cardReleaseDate) {
                // PIX: não liberado se created_at + dias > hoje
                $query->where(function ($q) use ($pixReleaseDate) {
                    $q->where('payment_method', 'pix')
                      ->where('created_at', '>', $pixReleaseDate);
                })
                // Cartão: não liberado se created_at + dias > hoje
                ->orWhere(function ($q) use ($cardReleaseDate) {
                    $q->where('payment_method', 'card')
                      ->where('created_at', '>', $cardReleaseDate);
                });
            })
            ->get();
        
        // Soma referrer_amount (comissões quando indicado assina) + creator_affiliate_amount (comissões quando criador indicado vende)
        $pendingAmount = $pendingCommissions->sum(function($subscription) {
            return (float) $subscription->referrer_amount + (float) $subscription->creator_affiliate_amount;
        });
        
        return (float) $pendingAmount;
    }

    /**
     * Conta afiliados ativos (usuários indicados com assinatura ativa)
     */
    public function getActiveAffiliatesCount(): int
    {
        $referredUserIds = $this->referrals()->pluck('referred_user_id');
        
        return \App\Models\Subscription::whereIn('user_id', $referredUserIds)
            ->where('is_active', true)
            ->where('end_date', '>=', now()->toDateString())
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * Conta total de indicações realizadas
     */
    public function getTotalReferralsCount(): int
    {
        return $this->referrals()->count();
    }

    /**
     * Saques do afiliado (com type = 'affiliate')
     */
    public function affiliateWithdrawals()
    {
        return $this->hasMany(\App\Models\Withdrawal::class)->where('type', 'affiliate');
    }

    /**
     * Retorna a URL completa da foto de perfil da API externa
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (!$this->profile_photo) {
            return null;
        }
        
        // Retorna URL local da pasta _files_
        return asset('_files_/' . $this->profile_photo);
    }

    /**
     * Retorna a URL completa da foto de capa
     */
    public function getCoverPhotoUrlAttribute(): ?string
    {
        if (!$this->cover_photo) {
            return null;
        }
        
        // Retorna URL local da pasta _files_
        return asset('_files_/' . $this->cover_photo);
    }

    /**
     * Retorna a URL completa do documento frente da API externa
     */
    public function getCreatorDocumentFrontUrlAttribute(): ?string
    {
        if (!$this->creator_document_front) {
            return null;
        }
        
        // Retorna URL local
        return asset('_files_/documents/' . $this->creator_document_front);
    }

    /**
     * Retorna a URL completa do documento verso da API externa
     */
    public function getCreatorDocumentBackUrlAttribute(): ?string
    {
        if (!$this->creator_document_back) {
            return null;
        }
        
        // Retorna URL local
        return asset('_files_/documents/' . $this->creator_document_back);
    }

    /**
     * Retorna a URL completa da selfie da API externa
     */
    public function getCreatorSelfieUrlAttribute(): ?string
    {
        if (!$this->creator_selfie) {
            return null;
        }
        
        // Retorna URL local
        return asset('_files_/documents/' . $this->creator_selfie);
    }

    /**
     * Verifica se o usuário é administrador
     */
    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }
}
