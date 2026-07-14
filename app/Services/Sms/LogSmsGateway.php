<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use Illuminate\Support\Facades\Log;

final class LogSmsGateway implements SmsGateway
{
    public function send(string $to, string $message): array
    {
        Log::info('PRMS SMS', [
            'to' => $to,
            'message' => $message,
        ]);

        return [
            'success' => true,
            'provider_response' => 'logged',
        ];
    }
}
