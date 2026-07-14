<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use App\Support\SafeReport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Sends SMS via Messaging Service API V2 (https://messaging-service.co.tz).
 */
final class MessagingServiceSmsGateway implements SmsGateway
{
    public function send(string $to, string $message): array
    {
        $url = $this->resolveUrl();

        if ($url === '') {
            Log::warning('PRMS SMS HTTP driver missing endpoint URL', ['to' => $to]);

            return [
                'success' => false,
                'provider_response' => 'missing_url',
            ];
        }

        $token = (string) $this->config('token', '');

        if ($token === '') {
            Log::warning('PRMS SMS HTTP driver missing bearer token', ['to' => $to]);

            return [
                'success' => false,
                'provider_response' => 'missing_token',
            ];
        }

        try {
            $payload = [
                'from' => (string) $this->config('sender_id', 'MoCU-PRMS'),
                'to' => $this->formatRecipient($to),
                'text' => $message,
                'flash' => (int) $this->config('flash', 0),
                'reference' => 'prms-'.Str::lower(Str::random(12)),
            ];

            $response = Http::timeout(15)
                ->withToken($token)
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);

            return $this->parseResponse($response->status(), $response->body(), $response->json(), $payload['to']);
        } catch (\Throwable $e) {
            SafeReport::call($e);

            return [
                'success' => false,
                'provider_response' => $e->getMessage(),
            ];
        }
    }

    private function formatRecipient(string $to): string
    {
        return preg_replace('/\D+/', '', ltrim(trim($to), '+')) ?? '';
    }

    private function resolveUrl(): string
    {
        $override = trim((string) $this->config('url', ''));

        if ($override !== '') {
            return $override;
        }

        $baseUrl = rtrim((string) $this->config('base_url', ''), '/');

        if ($baseUrl === '') {
            return '';
        }

        $path = (bool) $this->config('test_mode', false)
            ? (string) $this->config('test_endpoint', '/api/sms/v2/test/text/single')
            : (string) $this->config('endpoint', '/api/sms/v2/text/single');

        return $baseUrl.'/'.ltrim($path, '/');
    }

    /**
     * @param  array<string, mixed>|null  $body
     * @return array{success: bool, provider_response: string}
     */
    private function parseResponse(int $status, string $rawBody, ?array $body, string $to): array
    {
        $providerResponse = mb_substr($rawBody, 0, 500);

        if ($status < 200 || $status >= 300) {
            Log::warning('PRMS SMS HTTP failed', [
                'to' => $to,
                'status' => $status,
                'body' => $rawBody,
            ]);

            return [
                'success' => false,
                'provider_response' => $providerResponse,
            ];
        }

        if ($body !== null && ! $this->responseIndicatesAccepted($body)) {
            Log::warning('PRMS SMS HTTP returned non-success status in body', [
                'to' => $to,
                'body' => $providerResponse,
            ]);

            return [
                'success' => false,
                'provider_response' => $providerResponse,
            ];
        }

        return [
            'success' => true,
            'provider_response' => $providerResponse,
        ];
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function responseIndicatesAccepted(array $body): bool
    {
        $messages = $body['messages'] ?? null;

        if (! is_array($messages) || $messages === []) {
            return true;
        }

        $first = $messages[0] ?? null;

        if (! is_array($first)) {
            return true;
        }

        $status = $first['status'] ?? null;

        if (! is_array($status)) {
            return true;
        }

        $groupName = strtoupper((string) ($status['groupName'] ?? ''));
        $statusName = strtoupper((string) ($status['name'] ?? ''));

        if (in_array($groupName, ['FAILED', 'REJECTED'], true)) {
            return false;
        }

        if (str_contains($statusName, 'REJECTED') || str_contains($statusName, 'FAILED')) {
            return false;
        }

        return true;
    }

    private function config(string $key, mixed $default = null): mixed
    {
        return config('services.sms.messaging_service.'.$key, $default);
    }
}
