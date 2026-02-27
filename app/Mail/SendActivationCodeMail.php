<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendActivationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $code;

    public function __construct($code)
    {
        $this->code = $code;
    }

    public function build()
    {
        return $this->subject('Tu Código de Activación - AutomaCo')
            ->html("
                        <h2>¡Gracias por tu compra!</h2>
                        <p>Aquí tienes tu código de activación:</p>
                        <h3 style='background: #f4f4f4; padding: 10px; display: inline-block; letter-spacing: 2px;'>{$this->code}</h3>
                        <p>Ingresa este código en la plataforma para activar tu cuenta.</p>
                    ");
    }
}