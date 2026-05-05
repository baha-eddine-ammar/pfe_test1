<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewStaffRegistrationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly User $registeredUser,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New InfraGuard user awaiting approval')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A new staff account has been registered and is waiting for approval.')
            ->line('Name: '.$this->registeredUser->name)
            ->line('Email: '.$this->registeredUser->email)
            ->line('Department: '.$this->registeredUser->department)
            ->action('Review User Requests', route('admin.users.index'))
            ->line('Approve or reject the request from the administration workspace.');
    }
}
