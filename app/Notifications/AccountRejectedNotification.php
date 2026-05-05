<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountRejectedNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your InfraGuard account request was declined')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your request for InfraGuard workspace access was declined by administration.')
            ->line('If you believe this was unexpected, please contact your Department Head or project administrator for clarification.');
    }
}
