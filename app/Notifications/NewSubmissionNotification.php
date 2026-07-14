<?php

namespace App\Notifications;

use App\Models\ProjectSubmission;
use App\Support\PrmsNotificationChannels;
use App\Support\PrmsSms;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSubmissionNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ProjectSubmission $submission) {}

    /**
     * @return list<string|class-string>
     */
    public function via(object $notifiable): array
    {
        return PrmsNotificationChannels::submissionAlert($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Project Submission Received')
            ->line("A new {$this->submission->stage} submission has been uploaded.")
            ->line("Title: {$this->submission->title}")
            ->action('Open Supervisor Workspace', route('supervisor.index'));
    }

    public function toSms(object $notifiable): string
    {
        return PrmsSms::formatBody(
            'New submission',
            "A {$this->submission->stage} submission \"{$this->submission->title}\" is ready for review.",
            'Log in to PRMS.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New submission received',
            'message' => "A {$this->submission->stage} submission titled '{$this->submission->title}' is ready for review.",
            'route' => route('supervisor.index'),
        ];
    }
}
