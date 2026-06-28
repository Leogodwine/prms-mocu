<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * In-app only: alerts administrators when a new account is provisioned.
 */
class AdminNewUserCredentialsNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $newUserName,
        public string $newUserEmail,
        public string $loginId,
        public string $temporaryPassword,
        public ?string $createdByName = null,
        public ?int $createdByUserId = null,
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

        $credentials = 'Reg. no / Staff ID: '.$this->loginId.'. Temporary password: '.$this->temporaryPassword.'.';

        $isCreator = $this->createdByUserId !== null
            && (int) $notifiable->id === $this->createdByUserId;

        if ($isCreator) {
            $message = 'You created an account for '.$this->newUserName.' ('.$this->newUserEmail.'). '
                .$credentials.' The user was notified by email and in-app message where delivery succeeded.';
        } elseif ($this->createdByName) {
            $message = $this->createdByName.' created an account for '.$this->newUserName.' ('.$this->newUserEmail.'). '
                .$credentials;
        } else {
            $message = 'A new account was created for '.$this->newUserName.' ('.$this->newUserEmail.'). '
                .$credentials;
        }

        return [
            'title' => $title,
            'message' => $message,
            'login_id' => $this->loginId,
            'temporary_password' => $this->temporaryPassword,
            'new_user_email' => $this->newUserEmail,
            'created_by_user_id' => $this->createdByUserId,
            'created_by_name' => $this->createdByName,
        ];
    }
}
