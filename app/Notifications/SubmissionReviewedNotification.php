<?php

namespace App\Notifications;

use App\Models\ProjectSubmission;
use App\Support\PrmsNotificationChannels;
use App\Support\PrmsSms;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubmissionReviewedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ProjectSubmission $submission,
        private readonly string $decision
    ) {}

    private function decisionLabel(): string
    {
        return match ($this->decision) {
            'approved' => 'approved',
            'rejected' => 'rejected',
            'needs_revision' => 'returned for revision',
            default => str_replace('_', ' ', $this->decision),
        };
    }

    /**
     * @return list<string|class-string>
     */
    public function via(object $notifiable): array
    {
        return PrmsNotificationChannels::reviewAlert($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = $this->decisionLabel();

        return (new MailMessage)
            ->subject('Submission Review Update')
            ->line("Your {$this->submission->stage} submission has been {$label}.")
            ->line("Title: {$this->submission->title}")
            ->action('Open Student Workspace', route('student.index'));
    }

    public function toSms(object $notifiable): string
    {
        $label = $this->decisionLabel();

        return PrmsSms::formatBody(
            'Submission reviewed',
            "Your {$this->submission->stage} submission \"{$this->submission->title}\" was {$label}.",
            'Log in to PRMS.'
        );
    }

    /**
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
