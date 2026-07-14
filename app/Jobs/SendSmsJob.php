<?php

namespace App\Jobs;

use App\Services\Sms\SmsSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSmsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $phone,
        public string $message,
        public ?int $userId = null,
    ) {}

    public function handle(SmsSender $sender): void
    {
        $sender->sendSync($this->phone, $this->message, $this->userId);
    }
}
