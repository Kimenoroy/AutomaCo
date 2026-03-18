<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Auth\Notifications\ResetPassword;


class PublicResetPassword extends ResetPassword
{
    use Queueable;

    protected function buildMailMessage($url)
    {
        return (new MailMessage)
            ->subject('Recupera tu contraseña en AutomaCo')
            ->view('mails.reset-password', [
                'url' => $url
            ]);
    }

    protected function resetUrl($notifiable)
    {
        $publicUrl = env('FRONTEND_URL_PUBLIC', 'http://localhost:5173');
        return $publicUrl . '/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->getEmailForPasswordReset());
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