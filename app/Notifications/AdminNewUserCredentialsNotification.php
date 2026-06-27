<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * In-app only: lets the acting admin retrieve username + temporary password
 * after provisioning an account (same values sent to the new user).
 */
class AdminNewUserCredentialsNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $newUserName,
        public string $newUserEmail,
        public string $loginId,
        public string $temporaryPassword,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $title = 'Account created: '.$this->newUserName;

        $message = 'You created an account for '.$this->newUserName.' ('.$this->newUserEmail.'). '
            .'Reg. no / Staff ID: '.$this->loginId.'. '
            .'Temporary password: '.$this->temporaryPassword.'. '
            .'The user was notified by email and in-app message where delivery succeeded.';

        return [
            'title' => $title,
            'message' => $message,
            'login_id' => $this->loginId,
            'temporary_password' => $this->temporaryPassword,
            'new_user_email' => $this->newUserEmail,
        ];
    }
}
