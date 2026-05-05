<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly User $approvedBy,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Your InfraGuard account has been approved')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your InfraGuard account has been approved by '.$this->approvedBy->name.'.');

        if (method_exists($notifiable, 'hasVerifiedEmail') && ! $notifiable->hasVerifiedEmail()) {
            $message->line('Please complete email verification using the separate verification email before accessing the full workspace.');
        }

        return $message
            ->action('Go to Sign In', route('login'))
            ->line('Once verified, you can access the monitoring platform normally.');
    }
}
