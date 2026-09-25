<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\PaymentTransaction;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\VideoCall;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Chamada de vídeo paga no chat (bento, áudio 11; spec docs/superpowers/specs/2026-09-24).
 *
 * Criadora configura preço e duração; o fã pede e paga com saldo; o dinheiro fica reservado
 * na video_calls até ela entrar na sala. Pagamento é SEMPRE débito da carteira, como a
 * mensagem trancada: sem saldo, o fã recarrega levando video_call_id e o webhook conclui.
 * Toda transição grava uma mensagem 'video_call' na conversa: é ela que avisa o outro lado.
 */
class VideoCallController extends Controller
{
    private const TZ = 'America/Sao_Paulo';

    /** POST /chat/{conversationId}/chamada — o fã pede. */
    public function pedir(Request $request, $conversationId)
    {
        $user = Auth::user();
        $conversa = Conversation::findOrFail($conversationId);

        if ($erro = self::porQueNaoPodePedir($conversa, $user)) {
            return response()->json(['success' => false, 'message' => $erro], 400);
        }

        $request->validate(['suggested_at' => 'nullable|date']);
        $sugestao = $request->filled('suggested_at')
            ? Carbon::parse($request->input('suggested_at'), self::TZ)->utc()
            : null;

        $criadora = User::withoutGlobalScope('active')->find($conversa->creator_id);
        $preco = round((float) $criadora->video_call_price, 2);

        $chamada = VideoCall::create([
            'conversation_id'  => $conversa->id,
            'creator_id'       => $criadora->id,
            'user_id'          => $user->id,
            'price'            => $preco,
            'duration_minutes' => (int) $criadora->video_call_minutes,
            'status'           => 'awaiting_payment',
            'suggested_at'     => $sugestao,
        ]);

        $wallet = $user->getOrCreateWallet();
        if (round((float) $wallet->balance, 2) < $preco) {
            $falta = round($preco - (float) $wallet->balance, 2);

            return response()->json([
                'success'    => false,
                'recarregar' => true,
                'message'    => 'Faltam R$ ' . number_format($falta, 2, ',', '.') . ' no seu saldo.',
                'redirect'   => route('wallet.index', ['amount' => $preco, 'video_call_id' => $chamada->id]),
            ], 400);
        }

        try {
            $chamada = self::pagar($chamada);
        } catch (\Throwable $e) {
            Log::error('CHAMADA - FALHA NO PAGAMENTO (saldo devolvido pelo rollback)', [
                'user_id' => $user->id, 'video_call_id' => $chamada->id, 'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Não foi possível pedir a chamada. Seu saldo não foi debitado.'], 500);
        }

        return response()->json([
            'success'      => true,
            'message'      => 'Pedido enviado! O valor fica reservado até a chamada acontecer.',
            'chat_message' => $chamada->message->load('user')->toChatPayload($user),
        ]);
    }

    /**
     * O débito em si: awaiting_payment → requested. Estático porque o webhook da recarga chama
     * daqui. Idempotente: rodar de novo devolve a chamada como está.
     */
    public static function pagar(VideoCall $chamada, string $formaOriginal = 'wallet'): VideoCall
    {
        return DB::transaction(function () use ($chamada, $formaOriginal) {
            $chamada = VideoCall::lockForUpdate()->find($chamada->id);
            if ($chamada->status !== 'awaiting_payment') {
                return $chamada;
            }

            // Um pedido aberto por conversa (clique duplo, dois pedidos ao mesmo tempo)
            $outra = VideoCall::where('conversation_id', $chamada->conversation_id)
                ->whereIn('status', VideoCall::ABERTAS)->lockForUpdate()->exists();
            if ($outra) {
                throw new \RuntimeException('Já existe um pedido de chamada aberto nesta conversa.');
            }

            $preco = round((float) $chamada->price, 2);
            $wallet = Wallet::where('user_id', $chamada->user_id)->lockForUpdate()->first();
            if (! $wallet || round((float) $wallet->balance, 2) < $preco) {
                throw new \RuntimeException('Saldo insuficiente na carteira.');
            }

            $criadora = User::withoutGlobalScope('active')->find($chamada->creator_id);
            $wallet->subtractBalance($preco, 'Chamada de vídeo com @' . ($criadora->username ?? 'criadora') . ' (reservado)');

            $transacao = PaymentTransaction::create([
                'user_id'        => $chamada->user_id,
                'video_call_id'  => $chamada->id,
                'creator_id'     => $chamada->creator_id,
                'request_number' => (string) Str::uuid(),
                'type'           => 'wallet',
                'status'         => 'paid_out',
                'amount'         => $preco,
                'note'           => $formaOriginal === 'card'
                    ? 'Chamada de vídeo paga com saldo recarregado no cartão'
                    : 'Chamada de vídeo paga com saldo da carteira',
            ]);

            // Mesma divisão da mensagem trancada: percentual do cartão se o saldo veio de cartão;
            // afiliado sai da parte da plataforma.
            $percentual = $formaOriginal === 'card'
                ? PlatformSetting::getPlatformPercentageCard()
                : PlatformSetting::getPlatformPercentage();
            $daPlataforma = round($preco * $percentual / 100, 2);
            $daCriadora   = round($preco - $daPlataforma, 2);
            [$afiliadoId, $doAfiliado] = PlatformSetting::comissaoDoAfiliado($chamada->creator_id, $preco);
            $daPlataforma = round($daPlataforma - $doAfiliado, 2);

            $chamada->update([
                'status'                 => 'requested',
                'payment_transaction_id' => $transacao->id,
                'amount_paid'            => $preco,
                'platform_percentage'    => $percentual,
                'platform_amount'        => $daPlataforma,
                'affiliate_user_id'      => $afiliadoId,
                'affiliate_amount'       => $doAfiliado,
                'creator_amount'         => $daCriadora,
            ]);

            $texto = 'Pediu uma chamada de vídeo de ' . $chamada->duration_minutes . ' min por R$ ' . number_format($preco, 2, ',', '.')
                . ($chamada->suggested_at ? '. Sugeriu ' . VideoCall::rotuloHorario($chamada->suggested_at) . '.' : '.');
            $msg = self::mensagem($chamada, $chamada->user_id, $texto);
            $chamada->update(['message_id' => $msg->id]);

            return $chamada->fresh();
        });
    }

    /**
     * GET /chat/chamada/{videoCall}/entrar — abre a sala. Única porta pro LiveKit.
     * Criadora entrando em 'scheduled' vira 'done' aqui mesmo ("ela entrou = aconteceu",
     * decisão do Pedro 24/09): o sinal é nosso, o webhook só complementa.
     */
    public function entrar(VideoCall $videoCall, \App\Services\LiveKitService $livekit)
    {
        $user = Auth::user();
        $souCriadora = $videoCall->creator_id === $user->id;
        if (! $souCriadora && $videoCall->user_id !== $user->id) {
            abort(403, 'Você não participa desta chamada.');
        }
        if (! PlatformSetting::isVideoCallsEnabled() || ! $livekit->configurado()) {
            return redirect()->route('chat.show', $videoCall->conversation_id)->with('error', 'Chamada de vídeo indisponível no momento.');
        }
        if (! $videoCall->janelaAberta()) {
            return redirect()->route('chat.show', $videoCall->conversation_id)->with('error', 'Fora do horário da chamada.');
        }

        if ($souCriadora && $videoCall->status === 'scheduled') {
            DB::transaction(function () use ($videoCall) {
                $c = VideoCall::lockForUpdate()->find($videoCall->id);
                if ($c->status === 'scheduled') {
                    $c->update(['status' => 'done', 'creator_joined_at' => now(), 'room_name' => 'chamada-' . $c->id]);
                    self::mensagem($c, $c->creator_id, 'Entrou na chamada.');
                }
            });
            $videoCall->refresh();
        }
        if (! $videoCall->room_name) {
            $videoCall->update(['room_name' => 'chamada-' . $videoCall->id]);
        }

        $fim = $videoCall->fimDaChamada();
        $tolerancia = PlatformSetting::getVideoCallToleranceMinutes();
        // Token vale até o fim da duração (mais a tolerância enquanto ela não entrou), nunca menos de 1 min
        $limite = $videoCall->status === 'done' ? $fim : $fim->copy()->addMinutes($tolerancia);
        $ttl = max(60, now()->diffInSeconds($limite, false));
        $outro = User::withoutGlobalScope('active')->find($souCriadora ? $videoCall->user_id : $videoCall->creator_id);

        return view('chat.chamada', [
            'chamada'     => $videoCall,
            'token'       => $livekit->tokenDeAcesso($videoCall->room_name, $user->id, $souCriadora ? '@' . $user->username : $user->name, (int) $ttl),
            'wsUrl'       => $livekit->wsUrl(),
            'souCriadora' => $souCriadora,
            'outroNome'   => $souCriadora ? $outro->name : '@' . $outro->username,
            // fim em epoch (segundos) pro cronômetro não depender do relógio do celular
            'fimEpoch'    => $videoCall->status === 'done' ? $fim->getTimestamp() : null,
            'esperando'   => $videoCall->status !== 'done',
        ]);
    }

    /**
     * GET /chat/chamada/{videoCall}/estado — a página da sala consulta enquanto espera a
     * criadora (teste real 25/09: o fã que entrava antes ficava em "aguardando" pra sempre).
     */
    public function estado(VideoCall $videoCall)
    {
        $user = Auth::user();
        if ($videoCall->creator_id !== $user->id && $videoCall->user_id !== $user->id) {
            abort(403);
        }
        $fim = $videoCall->status === 'done' ? $videoCall->fimDaChamada() : null;

        return response()->json([
            'status'   => $videoCall->status,
            'fimEpoch' => $fim ? $fim->getTimestamp() : null,
            'ended'    => $videoCall->ended_at !== null,
        ]);
    }

    /** Recarga que nasceu de um pedido sem saldo: paga agora. Chamado pelo webhook e pelo cartão. */
    public static function concluirAposRecarga(PaymentTransaction $transacao): void
    {
        if (! $transacao->video_call_id) {
            return;
        }
        try {
            $chamada = VideoCall::find($transacao->video_call_id);
            if ($chamada && $chamada->status === 'awaiting_payment') {
                self::pagar($chamada, $transacao->type);
                Log::info('CHAMADA - PAGA APOS RECARGA', ['transaction_id' => $transacao->id, 'video_call_id' => $chamada->id]);
            }
        } catch (\Throwable $e) {
            // O saldo já entrou na carteira: o fã só precisa pedir de novo. Não derruba o webhook.
            Log::error('CHAMADA - FALHA AO PAGAR APOS RECARGA', ['transaction_id' => $transacao->id, 'error' => $e->getMessage()]);
        }
    }

    /** POST /chat/chamada/{videoCall}/marcar — criadora marca ou remarca. Vazio = agora. */
    public function marcar(Request $request, VideoCall $videoCall)
    {
        $user = Auth::user();
        if ($videoCall->creator_id !== $user->id || ! $videoCall->isOpen()) {
            return response()->json(['success' => false, 'message' => 'Este pedido não está aberto.'], 400);
        }

        $request->validate(['scheduled_at' => 'nullable|date'], ['scheduled_at.date' => 'Horário inválido.']);
        $horario = $request->filled('scheduled_at')
            ? Carbon::parse($request->input('scheduled_at'), self::TZ)->utc()
            : now();

        if ($horario->lt(now()->subMinutes(5))) {
            return response()->json(['success' => false, 'message' => 'Esse horário já passou.'], 400);
        }

        $remarcou = $videoCall->status === 'scheduled';
        $videoCall->update(['status' => 'scheduled', 'scheduled_at' => $horario]);
        $msg = self::mensagem($videoCall, $user->id, ($remarcou ? 'Remarcou' : 'Marcou') . ' a chamada pra ' . VideoCall::rotuloHorario($horario) . '.');

        return response()->json(['success' => true, 'chat_message' => $msg->load('user')->toChatPayload($user)]);
    }

    /** POST /chat/chamada/{videoCall}/recusar — criadora recusa, estorno na hora. */
    public function recusar(VideoCall $videoCall)
    {
        $user = Auth::user();
        if ($videoCall->creator_id !== $user->id || ! $videoCall->isOpen()) {
            return response()->json(['success' => false, 'message' => 'Este pedido não está aberto.'], 400);
        }

        $msg = self::devolver($videoCall, 'refused');

        return response()->json(['success' => true, 'chat_message' => $msg?->load('user')->toChatPayload($user)]);
    }

    /**
     * Devolve ao fã como saldo na carteira e fecha a chamada. Lock na linha e reconferência
     * do estado: cron rodando duas vezes ou clique repetido não credita duas vezes.
     * Devolve a mensagem gravada, ou null se não havia o que devolver.
     */
    public static function devolver(VideoCall $chamada, string $motivo, bool $forcar = false): ?Message
    {
        return DB::transaction(function () use ($chamada, $motivo, $forcar) {
            $chamada = VideoCall::lockForUpdate()->find($chamada->id);
            // $forcar = admin devolvendo uma chamada 'done' por denúncia (única intervenção humana prevista)
            if (! $chamada || ! ($chamada->isOpen() || ($forcar && $chamada->status === 'done'))) {
                return null;
            }

            $fa = User::withoutGlobalScope('active')->find($chamada->user_id);
            $criadora = User::withoutGlobalScope('active')->find($chamada->creator_id);
            $wallet = Wallet::where('user_id', $fa->id)->lockForUpdate()->first() ?? $fa->getOrCreateWallet();
            $wallet->addBalance(
                round((float) $chamada->amount_paid, 2),
                null,
                'Devolução da chamada de vídeo com @' . ($criadora->username ?? 'criadora'),
                'Motivo: ' . $motivo,
                $chamada->payment_transaction_id
            );

            $chamada->update(['status' => 'refunded', 'refund_reason' => $motivo, 'refunded_at' => now()]);

            $valor = 'R$ ' . number_format((float) $chamada->amount_paid, 2, ',', '.');
            $texto = match ($motivo) {
                'refused' => 'Recusou a chamada. ' . $valor . ' voltaram pra carteira do fã.',
                'no_show' => 'A criadora não entrou na chamada. ' . $valor . ' voltaram pra carteira do fã.',
                'admin'   => 'A equipe devolveu a chamada. ' . $valor . ' voltaram pra carteira do fã.',
                default   => 'O pedido passou do prazo. ' . $valor . ' voltaram pra carteira do fã.',
            };

            Log::info('CHAMADA - DEVOLVIDA', ['video_call_id' => $chamada->id, 'motivo' => $motivo, 'valor' => $chamada->amount_paid]);

            return self::mensagem($chamada, $chamada->creator_id, $texto);
        });
    }

    /** Mensagem 'video_call' na conversa: é o que atualiza a tela dos dois lados. */
    private static function mensagem(VideoCall $chamada, int $autorId, string $texto): Message
    {
        $msg = Message::create([
            'conversation_id' => $chamada->conversation_id,
            'user_id'         => $autorId,
            'message_type'    => 'video_call',
            'content'         => $texto,
            'video_call_id'   => $chamada->id,
        ]);
        Conversation::where('id', $chamada->conversation_id)->update(['last_message_at' => now()]);

        return $msg;
    }

    /** Motivo pelo qual este fã não pode pedir nesta conversa, ou null se pode. */
    public static function porQueNaoPodePedir(Conversation $conversa, ?User $user): ?string
    {
        if (! PlatformSetting::isVideoCallsEnabled()) {
            return 'Chamada de vídeo ainda não está disponível.';
        }
        if (! $user || $conversa->subscriber_id !== $user->id) {
            return 'Só o fã da conversa pode pedir a chamada.';
        }
        $criadora = User::withoutGlobalScope('active')->find($conversa->creator_id);
        if (! $criadora || ! $criadora->video_call_enabled || (float) $criadora->video_call_price < 1 || (int) $criadora->video_call_minutes < 1) {
            return 'Esta criadora não está oferecendo chamada de vídeo.';
        }
        if (VideoCall::where('conversation_id', $conversa->id)->whereIn('status', VideoCall::ABERTAS)->exists()) {
            return 'Já existe um pedido de chamada aberto nesta conversa.';
        }

        return null;
    }
}
