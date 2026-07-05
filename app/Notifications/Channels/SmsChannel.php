<?php

namespace App\Notifications\Channels;

use App\Services\Sms\SmsSender;
use App\Support\PrmsNotificationChannels;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    public function __construct(private readonly SmsSender $sender) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $phone = PrmsNotificationChannels::phoneFor($notifiable);

        if ($phone === null) {
            return;
        }

        $message = $notification->toSms($notifiable);

        if (! is_string($message) || trim($message) === '') {
            return;
        }

        $this->sender->send($phone, $message);
    }
}
