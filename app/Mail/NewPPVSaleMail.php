<?php

namespace App\Mail;

use App\Models\PostPurchase;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewPPVSaleMail extends Mailable
{
    use Queueable, SerializesModels;

    public PostPurchase $purchase;

    public function __construct(PostPurchase $purchase)
    {
        $this->purchase = $purchase;
    }

    public function build()
    {
        return $this->subject('Nova venda de Conteúdo Único!')
                    ->view('emails.ppv-sale')
                    ->with(['purchase' => $this->purchase]);
    }
}
