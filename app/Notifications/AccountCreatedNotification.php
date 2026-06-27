<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $username,
        public string $initialPassword,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $loginUrl = route('login');

        return (new MailMessage)
            ->subject(config('app.name', 'PRMS').': your new account')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('An administrator has created an account for you on the Project and Research Management System.')
            ->line('**Reg. no / Staff ID:** '.$this->username)
            ->line('**Temporary password:** '.$this->initialPassword)
            ->line('This password was randomly generated. Treat it as confidential and change it at first sign-in.')
            ->line('**Staff:** sign in with your **university email** ('.$notifiable->email.') and this password. **Students:** sign in with your **registration number** and this password.')
            ->line('You must choose a new password after your first successful sign-in.')
            ->action('Sign in', $loginUrl)
            ->line('If you did not expect this message, contact your administrator.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $title = 'Your account was created';

        $message = 'Reg. no / Staff ID: '.$this->username
            .'. Temporary password: '.$this->initialPassword
            .'. Staff: use your university email to sign in. Students: use your registration number. You must change your password after first login.';

        return [
            'title' => $title,
            'message' => $message,
            'username' => $this->username,
            'initial_password' => $this->initialPassword,
        ];
    }
}
