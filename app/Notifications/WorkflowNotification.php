<?php

namespace App\Notifications;

use App\Support\PrmsNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkflowNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $title,
        protected string $message,
        protected ?string $actionUrl = null,
        protected ?string $actionText = null,
        protected string $toastType = 'info',
    ) {}

    /**
     * @return list<string|class-string>
     */
    public function via(object $notifiable): array
    {
        return PrmsNotificationChannels::workflow($notifiable);
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

    public function toSms(object $notifiable): string
    {
        $text = $this->title.': '.$this->message;

        if ($this->actionUrl) {
            $text .= ' '.$this->actionUrl;
        }

        return mb_substr($text, 0, 480);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $route = $this->actionUrl ?? route('notifications.index');

        return [
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'action_text' => $this->actionText,
            'route' => $route,
            'toast' => $this->toastType === 'success',
            'toast_type' => $this->toastType,
        ];
    }
}
