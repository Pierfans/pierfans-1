<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessagePurchase;
use App\Models\PaymentTransaction;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Mensagem trancada no chat (bento 21/09: "incluir mensagem de audio" e "venda de
 * conteudos avulsos"; preco: "cada uma escolhe").
 *
 * A criadora manda o conteudo JA trancado com preco e o fa paga pra abrir. Nao e
 * "cobra antes, entrega depois", que deixaria o fa pagando e esperando entrega, e
 * exigiria prazo, reembolso e disputa (decisao do pedro em 21/09).
 *
 * Desbloquear e SEMPRE debito da carteira. PIX e cartao nao compram mensagem: eles
 * recarregam a carteira, e o webhook chama este mesmo desbloqueio quando o dinheiro
 * cai. Um caminho de dinheiro so, testado uma vez.
 */
class PaidMessageController extends Controller
{
    /**
     * Desbloqueia com saldo. Faltando saldo, devolve pra onde recarregar levando a
     * mensagem junto, pro webhook abrir sozinho quando o pagamento cair.
     */
    public function unlock(Message $message)
    {
        $user = Auth::user();

        if ($erro = self::porQueNaoPode($message, $user)) {
            return response()->json(['success' => false, 'message' => $erro], 400);
        }

        $preco  = round((float) $message->price, 2);
        $wallet = $user->getOrCreateWallet();

        if (round((float) $wallet->balance, 2) < $preco) {
            $falta = round($preco - (float) $wallet->balance, 2);

            return response()->json([
                'success'    => false,
                'recarregar' => true,
                'message'    => 'Faltam R$ ' . number_format($falta, 2, ',', '.') . ' no seu saldo.',
                'redirect'   => route('wallet.index', ['amount' => $preco, 'message_id' => $message->id]),
            ], 400);
        }

        try {
            self::comprarComSaldo($message, $user);
        } catch (\Throwable $e) {
            Log::error('MENSAGEM PAGA - FALHA NO DESBLOQUEIO (saldo devolvido pelo rollback)', [
                'user_id'    => $user->id,
                'message_id' => $message->id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Não foi possível liberar o conteúdo. Seu saldo não foi debitado.',
            ], 500);
        }

        return response()->json([
            'success'   => true,
            'message'   => 'Conteúdo liberado!',
            'media_url' => route('chat.media', $message->id),
        ]);
    }

    /**
     * O debito em si. Publico e estatico porque o webhook da carteira chama daqui
     * quando o PIX/cartao da recarga cai, pra recarga e desbloqueio serem o mesmo
     * caminho de dinheiro.
     *
     * Roda inteiro dentro de transacao: se qualquer parte falhar, o saldo volta.
     */
    public static function comprarComSaldo(Message $message, User $user, string $formaOriginal = 'wallet'): MessagePurchase
    {
        $preco = round((float) $message->price, 2);

        return DB::transaction(function () use ($message, $user, $preco) {
            // Webhook reenviado ou clique duplo: devolve a compra que ja existe sem
            // debitar de novo. O unique (user_id, message_id) e a trava final.
            $jaComprou = MessagePurchase::where('user_id', $user->id)
                ->where('message_id', $message->id)
                ->first();

            if ($jaComprou) {
                return $jaComprou;
            }

            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if (!$wallet || round((float) $wallet->balance, 2) < $preco) {
                throw new \RuntimeException('Saldo insuficiente na carteira.');
            }

            // withoutGlobalScope: criadora desativada nao some do lancamento
            $criadora = User::withoutGlobalScope('active')->find($message->user_id);

            $wallet->subtractBalance($preco, 'Conteúdo no chat de @' . ($criadora->username ?? 'criadora'));

            $transacao = PaymentTransaction::create([
                'user_id'        => $user->id,
                'message_id'     => $message->id,
                'creator_id'     => $message->user_id,
                'request_number' => (string) Str::uuid(),
                'type'           => 'wallet',
                'status'         => 'paid_out',
                'amount'         => $preco,
                'note'           => $formaOriginal === 'card'
                    ? 'Mensagem do chat paga com saldo recarregado no cartão'
                    : 'Mensagem do chat paga com saldo da carteira',
            ]);

            // Bento 21/09 mandou a tabela: "Chat de 100 = 76% criadora - 24% pierfans" na
            // linha do cartao. Aqui a compra e sempre com saldo, entao o que manda e de onde
            // o saldo veio: recarga no cartao usa o percentual do cartao, recarga no PIX e
            // saldo que ja estava na carteira usam o normal.
            $percentual = $formaOriginal === 'card'
                ? PlatformSetting::getPlatformPercentageCard()
                : PlatformSetting::getPlatformPercentage();
            $daPlataforma = round($preco * $percentual / 100, 2);

            return MessagePurchase::create([
                'user_id'                => $user->id,
                'message_id'             => $message->id,
                'creator_id'             => $message->user_id,
                'payment_transaction_id' => $transacao->id,
                'amount_paid'            => $preco,
                'platform_percentage'    => $percentual,
                'platform_amount'        => $daPlataforma,
                'creator_amount'         => round($preco - $daPlataforma, 2),
                'purchased_at'           => now(),
            ]);
        });
    }

    /**
     * Chamado quando uma recarga de carteira cai (PIX pelo webhook, cartão na hora).
     *
     * Se a recarga nasceu de um "quero abrir esta mensagem e não tenho saldo", abre agora.
     * É o mesmo comprarComSaldo do clique normal: recarga e desbloqueio nunca viram dois
     * caminhos de dinheiro diferentes.
     */
    public static function desbloquearAposRecarga(PaymentTransaction $transacao): void
    {
        if (!$transacao->message_id) {
            return;
        }

        try {
            $mensagem = Message::find($transacao->message_id);
            $comprador = User::withoutGlobalScope('active')->find($transacao->user_id);

            if (!$mensagem || !$comprador || !$mensagem->isPaid()) {
                return;
            }

            // O tipo da recarga e o que define a divisao: cartao cai em 76/24.
            self::comprarComSaldo($mensagem, $comprador, $transacao->type);

            Log::info('MENSAGEM PAGA - ABERTA APOS RECARGA', [
                'transaction_id' => $transacao->id,
                'message_id'     => $mensagem->id,
                'user_id'        => $comprador->id,
            ]);
        } catch (\Throwable $e) {
            // O saldo já entrou na carteira, então o dinheiro do fã não sumiu: ele só
            // precisa clicar em desbloquear de novo. Não pode derrubar o webhook.
            Log::error('MENSAGEM PAGA - FALHA AO ABRIR APOS RECARGA', [
                'transaction_id' => $transacao->id,
                'message_id'     => $transacao->message_id,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    /**
     * Entrega o arquivo da mensagem.
     *
     * Conteudo pago mora no disco privado e so sai por aqui, depois da verificacao. No
     * disco publico, quem tivesse o link baixava sem pagar — foi a licao dos documentos
     * de identidade, que sairam da pasta publica em 21/07/2026.
     */
    public function media(Message $message)
    {
        $user = Auth::user();
        $conversa = $message->conversation;

        $daConversa = $conversa
            && ($conversa->creator_id === $user->id || $conversa->subscriber_id === $user->id);

        if (!$daConversa && !$user->is_admin) {
            abort(403, 'Sem acesso a esta conversa.');
        }

        if (!$message->file_path) {
            abort(404);
        }

        if (!$message->isUnlockedFor($user)) {
            abort(403, 'Conteúdo ainda não liberado.');
        }

        // file_disk vazio = mensagem antiga, de antes da mensagem paga: mora no 'public'
        $disco = Storage::disk($message->file_disk ?: 'public');

        if (!$disco->exists($message->file_path)) {
            Log::warning('MENSAGEM - ARQUIVO SUMIDO', [
                'message_id' => $message->id,
                'disk'       => $message->file_disk ?: 'public',
                'path'       => $message->file_path,
            ]);
            abort(404);
        }

        return $disco->response($message->file_path);
    }

    /**
     * Motivo pelo qual esta compra não pode acontecer, ou null se pode.
     */
    private static function porQueNaoPode(Message $message, ?User $user): ?string
    {
        if (!$user) {
            return 'Faça login para continuar.';
        }

        if (!$message->isPaid()) {
            return 'Esta mensagem não é paga.';
        }

        if ($message->user_id === $user->id) {
            return 'Você não precisa comprar o que você mesmo enviou.';
        }

        $conversa = $message->conversation;

        if (!$conversa || ($conversa->creator_id !== $user->id && $conversa->subscriber_id !== $user->id)) {
            return 'Você não participa desta conversa.';
        }

        if ($message->purchases()->where('user_id', $user->id)->exists()) {
            return 'Você já liberou este conteúdo.';
        }

        return null;
    }
}
