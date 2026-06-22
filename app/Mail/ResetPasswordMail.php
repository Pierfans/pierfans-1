<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $token;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $resetUrl = url('/reset-password?token=' . $this->token . '&email=' . urlencode($this->user->email));

        return $this->subject('Recuperação de Senha')
                    ->view('emails.reset-password')
                    ->with([
                        'user' => $this->user,
                        'token' => $this->token,
                        'resetUrl' => $resetUrl,
                    ]);
    }
}

