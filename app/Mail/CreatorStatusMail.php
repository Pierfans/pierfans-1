<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CreatorStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $type;

    /**
     * @param string $type approved | rejected | activated | deactivated
     */
    public function __construct(User $user, string $type)
    {
        $this->user = $user;
        $this->type = $type;
    }

    public function build()
    {
        $map = [
            'approved' => [
                'subject' => 'Seu cadastro de criadora foi aprovado!',
                'emoji'   => '🎉',
                'heading' => 'Cadastro aprovado!',
                'lines'   => [
                    'Parabéns! Seu cadastro de criadora na Pierfans foi aprovado.',
                    'Você já pode publicar seus conteúdos e começar a receber assinantes.',
                ],
                'cta'     => ['url' => url('/dashboard'), 'label' => 'Acessar minha conta'],
            ],
            'rejected' => [
                'subject' => 'Seu cadastro de criadora não foi aprovado',
                'emoji'   => '⚠️',
                'heading' => 'Cadastro não aprovado',
                'lines'   => array_values(array_filter([
                    'Revisamos seu cadastro de criadora na Pierfans e ele não foi aprovado.',
                    $this->user->creator_rejection_reason
                        ? 'Motivo: ' . $this->user->creator_rejection_reason
                        : null,
                    'Você pode corrigir o que for necessário e enviar seu cadastro de novo.',
                ])),
                'cta'     => ['url' => url('/creator'), 'label' => 'Enviar novamente'],
            ],
            'activated' => [
                'subject' => 'Seu perfil de criadora foi reativado',
                'emoji'   => '✅',
                'heading' => 'Perfil reativado',
                'lines'   => [
                    'Seu perfil de criadora na Pierfans foi reativado.',
                    'Seu conteúdo voltou a ficar visível e você já pode publicar normalmente.',
                ],
                'cta'     => ['url' => url('/dashboard'), 'label' => 'Acessar minha conta'],
            ],
            'deactivated' => [
                'subject' => 'Seu perfil de criadora foi desativado',
                'emoji'   => '⚠️',
                'heading' => 'Perfil desativado',
                'lines'   => [
                    'Seu perfil de criadora na Pierfans foi desativado e seu conteúdo não está mais visível.',
                    'Se você acredita que isso foi um engano, entre em contato com o suporte.',
                ],
                'cta'     => null,
            ],
        ];

        $content = $map[$this->type] ?? throw new \InvalidArgumentException("Tipo de email de criadora inválido: {$this->type}");

        return $this->subject($content['subject'])
                    ->view('emails.creator-status')
                    ->with(['user' => $this->user, 'content' => $content]);
    }
}
