<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class SuspiciousLoginAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $currentIp,
        private readonly ?string $previousIp,
        private readonly ?CarbonInterface $previousLoginAt,
        private readonly CarbonInterface $currentLoginAt,
        private readonly ?string $userAgent,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('New sign-in detected on your InfraGuard account')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('We noticed a new sign-in to your InfraGuard account from IP address '.$this->currentIp.'.')
            ->line('Time: '.$this->currentLoginAt->format('d M Y H:i:s'));

        if ($this->previousIp) {
            $message->line('Previous IP: '.$this->previousIp);
        }

        if ($this->previousLoginAt) {
            $message->line('Previous sign-in: '.$this->previousLoginAt->format('d M Y H:i:s'));
        }

        if ($this->userAgent) {
            $message->line('Device: '.Str::limit($this->userAgent, 160));
        }

        return $message
            ->action('Review Account Access', route('profile.edit'))
            ->line('If this was not you, change your password immediately and contact your administrator.');
    }
}
