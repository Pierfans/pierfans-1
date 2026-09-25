<?php

namespace App\Models;

use App\Support\VideoCallRules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pedido de chamada de vídeo paga no chat. O dinheiro sai da carteira do fã no pedido e
 * fica aqui, reservado, até a criadora entrar na sala (done). Ver spec de 24/09.
 *
 * Estados: awaiting_payment → requested → scheduled → done
 *                             requested/scheduled → refunded (refused | no_show | expired)
 */
class VideoCall extends Model
{
    protected $fillable = [
        'conversation_id', 'creator_id', 'user_id', 'message_id', 'payment_transaction_id',
        'price', 'duration_minutes', 'status', 'suggested_at', 'scheduled_at', 'creator_joined_at',
        'room_name', 'user_joined_at', 'ended_at',
        'amount_paid', 'platform_percentage', 'platform_amount',
        'affiliate_user_id', 'affiliate_amount', 'creator_amount',
        'refund_reason', 'refunded_at',
    ];

    protected $casts = [
        'price'               => 'decimal:2',
        'amount_paid'         => 'decimal:2',
        'platform_percentage' => 'decimal:2',
        'platform_amount'     => 'decimal:2',
        'affiliate_amount'    => 'decimal:2',
        'creator_amount'      => 'decimal:2',
        'suggested_at'        => 'datetime',
        'scheduled_at'        => 'datetime',
        'creator_joined_at'   => 'datetime',
        'user_joined_at'      => 'datetime',
        'ended_at'            => 'datetime',
        'refunded_at'         => 'datetime',
    ];

    public const ABERTAS = ['requested', 'scheduled'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id')->withoutGlobalScope('active');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withoutGlobalScope('active');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::ABERTAS, true);
    }

    public function janelaAberta(): bool
    {
        return VideoCallRules::janelaAberta(
            $this->status, $this->scheduled_at, $this->creator_joined_at,
            (int) $this->duration_minutes, now(), PlatformSetting::getVideoCallToleranceMinutes()
        );
    }

    /** Fim da chamada, contado no servidor: entrada da criadora (ou horário marcado) + duração. */
    public function fimDaChamada(): ?\Carbon\Carbon
    {
        $inicio = $this->creator_joined_at ?? $this->scheduled_at;

        return $inicio ? $inicio->copy()->addMinutes((int) $this->duration_minutes) : null;
    }

    public function motivoDevolucao(): ?string
    {
        return VideoCallRules::motivoDevolucao(
            $this->status, $this->scheduled_at, $this->created_at, $this->creator_joined_at,
            now(), PlatformSetting::getVideoCallToleranceMinutes()
        );
    }

    /** "sáb 27/09 às 21:00", em Brasília. */
    public static function rotuloHorario(?\DateTimeInterface $dt): ?string
    {
        if (! $dt) {
            return null;
        }
        $c = \Carbon\Carbon::instance($dt)->setTimezone('America/Sao_Paulo');
        $dias = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];

        return $dias[$c->dayOfWeek] . ' ' . $c->format('d/m') . ' às ' . $c->format('H:i');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'awaiting_payment' => 'Aguardando pagamento',
            'requested'        => 'Aguardando a criadora marcar',
            'scheduled'        => 'Marcada pra ' . self::rotuloHorario($this->scheduled_at),
            'done'             => 'Realizada',
            'refunded'         => 'Devolvida ao fã: ' . match ($this->refund_reason) {
                'refused' => 'a criadora recusou',
                'no_show' => 'a criadora não entrou',
                default   => 'passou do prazo',
            },
            default            => $this->status,
        };
    }

    /**
     * O que o chat manda pro navegador. Botões só pra quem pode: marcar e recusar são da
     * criadora com pedido aberto; entrar é dos dois, na janela. last_message_id diz qual
     * card da conversa é o atual (os anteriores viram histórico).
     */
    public function toPayload(?User $viewer): array
    {
        $souCriadora = $viewer && $viewer->id === $this->creator_id;
        $aberta = $this->isOpen();
        $sp = fn (?\Carbon\Carbon $c) => $c ? $c->copy()->setTimezone('America/Sao_Paulo')->format('Y-m-d\TH:i') : null;

        return [
            'id'               => $this->id,
            'status'           => $this->status,
            'status_label'     => $this->statusLabel(),
            'price'            => (float) $this->price,
            'duration_minutes' => (int) $this->duration_minutes,
            'suggested_local'  => $sp($this->suggested_at),   // pro datetime-local
            'scheduled_local'  => $sp($this->scheduled_at),
            'suggested_label'  => self::rotuloHorario($this->suggested_at),
            'scheduled_label'  => self::rotuloHorario($this->scheduled_at),
            'refund_reason'    => $this->refund_reason,
            'sou_criadora'     => $souCriadora,
            'pode_marcar'      => $souCriadora && $aberta,
            'pode_recusar'     => $souCriadora && $aberta,
            'pode_entrar'      => $this->janelaAberta(),
            'entrar_url'       => $this->janelaAberta() ? route('chat.chamada.entrar', $this->id) : null,
            'last_message_id'  => (int) Message::where('video_call_id', $this->id)->max('id'),
        ];
    }

    /** Quanto a criadora tem de chamada realizada, liberado ou ainda no prazo do chat. */
    public static function creatorAmount(int $creatorId, bool $released): float
    {
        $days = PlatformSetting::getChatReleaseDays();
        $date = $days == 0 ? now() : now()->subDays($days)->endOfDay();

        return (float) self::where('creator_id', $creatorId)->where('status', 'done')
            ->where('creator_joined_at', $released ? '<=' : '>', $date)
            ->sum('creator_amount');
    }

    public static function affiliateAmount(int $affiliateId, bool $released): float
    {
        $days = PlatformSetting::getChatReleaseDays();
        $date = $days == 0 ? now() : now()->subDays($days)->endOfDay();

        return (float) self::where('affiliate_user_id', $affiliateId)->where('status', 'done')
            ->where('creator_joined_at', $released ? '<=' : '>', $date)
            ->sum('affiliate_amount');
    }

    /** Reservado em pedidos abertos: nem dela nem do fã ainda. Só pra ela ver na tela de saque. */
    public static function reservado(int $creatorId): float
    {
        return (float) self::where('creator_id', $creatorId)->whereIn('status', self::ABERTAS)->sum('creator_amount');
    }
}
