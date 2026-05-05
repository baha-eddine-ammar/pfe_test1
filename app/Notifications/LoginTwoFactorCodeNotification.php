<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginTwoFactorCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $code,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your InfraGuard verification code')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Use the following one-time verification code to finish signing in to your InfraGuard account:')
            ->line($this->code)
            ->line('This code expires in 5 minutes and can only be used once.')
            ->line('If you did not attempt to sign in, change your password and contact your administrator.');
    }
}
