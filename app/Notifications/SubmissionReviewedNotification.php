<?php

namespace App\Notifications;

use App\Models\ProjectSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubmissionReviewedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ProjectSubmission $submission,
        private readonly string $decision
    ) {
    }

    /**
     * Friendly verb for the decision string. Keeps the database/mail
     * copy readable for `needs_revision` (which would otherwise read as
     * the snake-cased token).
     */
    private function decisionLabel(): string
    {
        return match ($this->decision) {
            'approved'       => 'approved',
            'rejected'       => 'rejected',
            'needs_revision' => 'returned for revision',
            default          => str_replace('_', ' ', $this->decision),
        };
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->notify_email_submission_reviewed) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $label = $this->decisionLabel();

        return (new MailMessage)
            ->subject('Submission Review Update')
            ->line("Your {$this->submission->stage} submission has been {$label}.")
            ->line("Title: {$this->submission->title}")
            ->action('Open Student Workspace', route('student.index'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Submission reviewed',
            'message' => "Your '{$this->submission->title}' submission was {$this->decisionLabel()}.",
            'route' => route('student.index'),
        ];
    }
}
