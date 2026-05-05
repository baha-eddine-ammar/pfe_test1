<?php

namespace App\Notifications;

use App\Models\DepartmentHeadInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DepartmentHeadInviteNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly DepartmentHeadInvite $invite,
        private readonly string $revealUrl,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('InfraGuard Department Head invite')
            ->greeting('Hello,')
            ->line('You have been invited to create a Department Head account for InfraGuard.')
            ->line('This invite is bound to '.$this->invite->invited_email.' and expires on '.$this->invite->expires_at?->format('d M Y H:i').'.')
            ->line('For security, the authorization code is not shown in this email.')
            ->action('Reveal One-Time Authorization Code', $this->revealUrl)
            ->line('Open the reveal link once, note the code carefully, and then continue with registration.');
    }
}
