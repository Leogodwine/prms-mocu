<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use App\Jobs\SendSmsJob;
use App\Models\SmsDeliveryLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

final class SmsSender
{
    public function __construct(private readonly SmsGateway $gateway) {}

    public function send(string $to, string $message, ?int $userId = null): bool
    {
        if ((string) config('queue.default') === 'sync') {
            return $this->sendSync($to, $message, $userId);
        }

        SendSmsJob::dispatch($to, $message, $userId);

        return true;
    }

    public function sendSync(string $to, string $message, ?int $userId = null): bool
    {
        $message = trim($message);

        if ($message === '') {
            return false;
        }

        if (! config('prms.sms.enabled', false)) {
            Log::info('PRMS SMS skipped (disabled)', [
                'to' => $to,
                'message' => $message,
            ]);

            $this->logDelivery($userId, $to, $message, 'skipped', 'disabled');

            return true;
        }

        $result = $this->gateway->send($to, $message);
        $status = $result['success'] ? 'sent' : 'failed';

        $this->logDelivery($userId, $to, $message, $status, $result['provider_response']);

        return $result['success'];
    }

    private function logDelivery(?int $userId, string $phone, string $message, string $status, ?string $providerResponse): void
    {
        if (! Schema::hasTable('sms_delivery_logs')) {
            return;
        }

        SmsDeliveryLog::query()->create([
            'user_id' => $userId,
            'phone' => $phone,
            'message' => mb_substr($message, 0, 500),
            'status' => $status,
            'provider_response' => $providerResponse,
            'created_at' => now(),
        ]);
    }
}
