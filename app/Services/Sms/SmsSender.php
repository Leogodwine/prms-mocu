<?php

namespace App\Services\Sms;

use App\Support\SafeReport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class SmsSender
{
    public function send(string $to, string $message): bool
    {
        $message = trim($message);

        if ($message === '') {
            return false;
        }

        $driver = (string) config('prms.sms.driver', 'log');

        if (! config('prms.sms.enabled', false)) {
            Log::info('PRMS SMS skipped (disabled)', [
                'to' => $to,
                'message' => $message,
            ]);

            return true;
        }

        if ($driver === 'http') {
            return $this->sendViaHttp($to, $message);
        }

        Log::info('PRMS SMS', [
            'to' => $to,
            'message' => $message,
        ]);

        return true;
    }

    private function sendViaHttp(string $to, string $message): bool
    {
        $url = (string) config('prms.sms.http_url', '');

        if ($url === '') {
            Log::warning('PRMS SMS HTTP driver missing PRMS_SMS_HTTP_URL', ['to' => $to]);

            return false;
        }

        try {
            $request = Http::timeout(15);

            $token = (string) config('prms.sms.http_token', '');
            if ($token !== '') {
                $request = $request->withToken($token);
            }

            $response = $request->post($url, [
                'to' => $to,
                'message' => $message,
                'sender' => (string) config('prms.sms.sender_id', 'MoCU-PRMS'),
            ]);

            if (! $response->successful()) {
                Log::warning('PRMS SMS HTTP failed', [
                    'to' => $to,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            SafeReport::call($e);

            return false;
        }
    }
}
