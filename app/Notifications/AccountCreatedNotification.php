<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountCreatedNotification extends Notification
{
    use Queueable;

    /**
     * @param  list<string>  $channels
     */
    public function __construct(
        public string $username,
        public string $initialPassword,
        public array $channels = ['database', 'mail'],
        public string $source = 'manual',
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var User $notifiable */
        $loginUrl = route('login');
        $isStudent = $notifiable->isStudentUser();
        $identifierLabel = $notifiable->displayIdentifierLabel();

        $intro = match ($this->source) {
            'bulk_import' => 'An administrator imported your account in bulk on the Project and Research Management System.',
            'password_reset' => 'An administrator reset your password on the Project and Research Management System.',
            default => 'An administrator has created an account for you on the Project and Research Management System.',
        };

        $subject = match ($this->source) {
            'password_reset' => config('app.name', 'PRMS').': password reset',
            default => config('app.name', 'PRMS').': your new account',
        };

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($intro)
            ->line('**'.$identifierLabel.':** '.$this->username)
            ->line('**Temporary password:** '.$this->initialPassword)
            ->line('This password was randomly generated. Treat it as confidential and change it at first sign-in.');

        if ($isStudent) {
            $mail->line('Sign in with your **registration number** and this password.');
        } else {
            $mail->line('Sign in with your **university email** ('.$notifiable->email.') and this password.');
        }

        return $mail
            ->line('You must choose a new password after your first successful sign-in.')
            ->action('Sign in', $loginUrl)
            ->line('If you did not expect this message, contact your administrator.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        /** @var User $notifiable */
        $isStudent = $notifiable->isStudentUser();
        $identifierLabel = $notifiable->displayIdentifierLabel();
        $title = match ($this->source) {
            'password_reset' => 'Your password was reset',
            default => 'Your account was created',
        };

        $signInHint = $isStudent
            ? 'Sign in with your registration number.'
            : 'Sign in with your university email ('.$notifiable->email.').';

        $message = $identifierLabel.': '.$this->username
            .'. Temporary password: '.$this->initialPassword
            .'. '.$signInHint.' You must change your password after first login.';

        return [
            'title' => $title,
            'message' => $message,
            'username' => $this->username,
            'identifier_label' => $identifierLabel,
            'initial_password' => $this->initialPassword,
            'source' => $this->source,
        ];
    }
}
