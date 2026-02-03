<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;


class ResetPasswordNotification extends Notification
{
    use Queueable;
    public $token;

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {

        $frontendUrl = env('URL');
        $url = $frontendUrl . '/reset-password?token=' . $this->token . '&email=' . $notifiable->getEmailForPasswordReset();

        return (new MailMessage)
            ->subject('Solicitud de restablecimiento de contraseña') // Asunto del correo
            ->greeting('¡Hola!') // Saludo inicial
            ->line('Recibiste este correo porque solicitaste restablecer tu contraseña.') // Texto cuerpo
            ->action('Restablecer Contraseña', $url) // Texto del botón y URL
            ->line('Si no solicitaste este cambio, no es necesario realizar ninguna acción.') // Despedida
            ->salutation('Saludos, el equipo de AutomaCo'); // Firma
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
