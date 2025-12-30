<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(protected string $token, protected string $frontendUrl)
    {
        //
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = rtrim($this->frontendUrl, '/').'/?token='.urlencode($this->token).'&email='.urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Redefinição de senha')
            ->line('Recebemos uma solicitação para redefinir sua senha.')
            ->action('Redefinir senha', $url)
            ->line('Se você não solicitou essa alteração, ignore este email.');
    }
}
