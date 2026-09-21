<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Avisos de estorno de cartao (bento 21/09: "informa o porque isso ta acontecendo,
 * que um cliente pagou e depois pediu estorno do cartao" e, pro assinante bloqueado,
 * "manda um email pra pessoa, se a gente ver que ela nao fez de proposito, liberamos").
 *
 * Reusa a view emails.creator-status, que e generica (emoji, heading, lines, cta).
 */
class ChargebackMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $type;
    public array $dados;

    /**
     * @param string $type creator | subscriber
     * @param array  $dados ['valor' => float, 'oQueEra' => string]
     */
    public function __construct(User $user, string $type, array $dados = [])
    {
        $this->user = $user;
        $this->type = $type;
        $this->dados = $dados;
    }

    public function build()
    {
        $valor   = number_format((float) ($this->dados['valor'] ?? 0), 2, ',', '.');
        $oQueEra = $this->dados['oQueEra'] ?? 'uma venda';

        $map = [
            'creator' => [
                'subject' => 'Uma venda sua foi estornada',
                'emoji'   => '⚠️',
                'heading' => 'Venda estornada',
                'lines'   => [
                    "Um cliente pagou {$oQueEra} no seu perfil com cartão de crédito e depois pediu estorno ao banco dele.",
                    "O banco devolveu o dinheiro para o cliente, então o valor de R$ {$valor} saiu do seu saldo.",
                    'Se o seu saldo ficou negativo, é porque esse valor já tinha sido sacado. Ele será descontado das próximas vendas.',
                    'O acesso do cliente ao conteúdo foi cancelado. Você não precisa fazer nada.',
                ],
                'cta'     => ['url' => url('/withdraw'), 'label' => 'Ver meu saldo'],
            ],
            'subscriber' => [
                'subject' => 'Sua conta na Pierfans foi bloqueada',
                'emoji'   => '⚠️',
                'heading' => 'Conta bloqueada',
                'lines'   => [
                    'Recebemos do banco um pedido de estorno de uma compra feita com cartão na sua conta, e por isso ela foi bloqueada.',
                    'Se você não reconhece essa compra, pode ser que seu cartão tenha sido usado por outra pessoa.',
                    'Se foi engano, responda este e-mail contando o que aconteceu que a gente analisa e libera sua conta.',
                ],
                'cta'     => null,
            ],
        ];

        $content = $map[$this->type] ?? throw new \InvalidArgumentException("Tipo de email de estorno inválido: {$this->type}");

        return $this->subject($content['subject'])
                    ->view('emails.creator-status')
                    ->with(['user' => $this->user, 'content' => $content]);
    }
}
