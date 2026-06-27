<?php

namespace App\Notifications;

use App\Models\ProjectSubmission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSubmissionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ProjectSubmission $submission)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->notify_email_new_submission) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Project Submission Received')
            ->line("A new {$this->submission->stage} submission has been uploaded.")
            ->line("Title: {$this->submission->title}")
            ->action('Open Supervisor Workspace', route('supervisor.index'));
    }

    /**
     * Get the array representation of the notification.
     *
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
