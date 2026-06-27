<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $title,
        protected string $message,
        protected ?string $actionUrl = null,
        protected ?string $actionText = null,
    ) {}

    public function via(object $notifiable): array
    {
        // Students: in-app only for registration acknowledgements (avoid unsolicited email).
        if (in_array($notifiable->role ?? '', ['project_student', 'research_student', 'normal_student', 'student'], true)) {
            return ['database'];
        }

        $channels = ['database'];

        if ($notifiable->notify_email_new_submission || $notifiable->notify_email_submission_reviewed) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->line($this->message);

        if ($this->actionUrl && $this->actionText) {
            $mail->action($this->actionText, $this->actionUrl);
        }

        return $mail->line('Thank you for using MoCU PRMS!');
    }

    public function toArray(object $notifiable): array
    {
        $route = $this->actionUrl ?? route('notifications.index');

        return [
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'action_text' => $this->actionText,
            'route' => $route,
        ];
    }
}
