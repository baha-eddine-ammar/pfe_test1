<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PendingApprovalNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('InfraGuard account request received')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your InfraGuard staff account request has been received and is now waiting for Department Head approval.')
            ->line('Once your account is approved, we will send you the next email needed to verify your address and finish activation.')
            ->action('Go to Sign In', route('login'))
            ->line('No action is needed from you until that review is complete.');
    }
}
