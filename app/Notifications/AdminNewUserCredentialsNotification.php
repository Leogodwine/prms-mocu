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
        public string $source = 'manual',
        public bool $isStudent = false,
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

        $identifierLabel = $this->isStudent ? 'Reg. no' : 'Staff ID';
        $credentials = $identifierLabel.': '.$this->loginId.'. Temporary password: '.$this->temporaryPassword.'.';

        $isCreator = $this->createdByUserId !== null
            && (int) $notifiable->id === $this->createdByUserId;

        $viaBulkImport = $this->source === 'bulk_import';
        $actionVerb = $viaBulkImport ? 'imported' : 'created';

        if ($isCreator) {
            $message = 'You '.$actionVerb.' an account for '.$this->newUserName.' ('.$this->newUserEmail.'). '
                .$credentials.' The user was notified by email and in-app message where delivery succeeded.';
        } elseif ($this->createdByName) {
            $message = $this->createdByName.' '.$actionVerb.' an account for '.$this->newUserName.' ('.$this->newUserEmail.'). '
                .$credentials;
        } else {
            $message = 'A new account was '.$actionVerb.' for '.$this->newUserName.' ('.$this->newUserEmail.'). '
                .$credentials;
        }

        if ($viaBulkImport) {
            $title = 'Bulk import: '.$this->newUserName;
        }

        return [
            'title' => $title,
            'message' => $message,
            'login_id' => $this->loginId,
            'identifier_label' => $identifierLabel,
            'temporary_password' => $this->temporaryPassword,
            'new_user_email' => $this->newUserEmail,
            'created_by_user_id' => $this->createdByUserId,
            'created_by_name' => $this->createdByName,
            'source' => $this->source,
        ];
    }
}
