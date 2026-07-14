<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use App\Support\SafeReport;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Sends SMS via the NextSMS HTTP API.
 *
 * Supports:
 * - Bearer token (PRMS_SMS_HTTP_TOKEN) or Basic auth (username/password)
 * - Official v1 payload { from, to, text } — api.nextsms.co.tz/.../text/single
 * - Legacy v1 payload { sender_id, mobile, message } — api.nextsms.com/.../send
 *
 * @see https://documenter.getpostman.com/view/4680389/SW7dX7JL
 */
final class NextSmsGateway implements SmsGateway
{
    public function send(string $to, string $message): array
    {
        $url = $this->resolveUrl();

        if ($url === '') {
            Log::warning('NextSMS gateway missing endpoint URL', ['to' => $to]);

            return [
                'success' => false,
                'provider_response' => 'missing_url',
            ];
        }

        if (! $this->hasCredentials()) {
            Log::warning('NextSMS gateway missing credentials', ['to' => $to]);

            return [
                'success' => false,
                'provider_response' => 'missing_credentials',
            ];
        }

        try {
            $recipient = $this->formatRecipient($to);
            $payload = $this->buildPayload($recipient, $message, $url);

            $response = $this->authenticatedRequest()
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);

            return $this->parseResponse($response->status(), $response->body(), $response->json(), $recipient);
        } catch (\Throwable $e) {
            SafeReport::call($e);

            return [
                'success' => false,
                'provider_response' => $e->getMessage(),
            ];
        }
    }

    private function hasCredentials(): bool
    {
        $auth = strtolower((string) $this->config('auth', 'basic'));

        if ($auth === 'basic') {
            if (trim((string) $this->config('basic_auth', '')) !== '') {
                return true;
            }

            return trim((string) $this->config('username', '')) !== ''
                && trim((string) $this->config('password', '')) !== '';
        }

        return trim((string) $this->config('token', '')) !== '';
    }

    private function authenticatedRequest(): PendingRequest
    {
        $request = Http::timeout((int) $this->config('timeout', 15));

        $auth = strtolower((string) $this->config('auth', 'basic'));

        if ($auth === 'basic') {
            $encoded = $this->resolveBasicCredentials();

            return $request->withHeaders([
                'Authorization' => 'Basic '.$encoded,
            ]);
        }

        return $request->withToken((string) $this->config('token', ''));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(string $recipient, string $message, string $url): array
    {
        $senderId = (string) $this->config('sender_id', 'PRMSMoCU');
        $format = (string) $this->config('payload_format', 'auto');

        if ($format === 'auto') {
            $format = str_contains(strtolower($url), '/send') ? 'sender_mobile_message' : 'from_to_text';
        }

        if ($format === 'sender_mobile_message') {
            $mobile = (bool) $this->config('use_local_mobile_format', false)
                ? $this->toLocalMobile($recipient)
                : $recipient;

            return array_filter([
                'sender_id' => $senderId,
                'mobile' => $mobile,
                'message' => $message,
                'callback_url' => $this->config('callback_url'),
            ], fn ($value) => $value !== null && $value !== '');
        }

        return array_filter([
            'from' => $senderId,
            'to' => $recipient,
            'text' => $message,
            'reference' => 'prms-'.Str::lower(Str::random(12)),
            'callback_url' => $this->config('callback_url'),
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function formatRecipient(string $to): string
    {
        $digits = preg_replace('/\D+/', '', ltrim(trim($to), '+')) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = '255'.substr($digits, 1);
        }

        return $digits;
    }

    private function toLocalMobile(string $internationalDigits): string
    {
        if (str_starts_with($internationalDigits, '255') && strlen($internationalDigits) >= 12) {
            return '0'.substr($internationalDigits, 3);
        }

        return $internationalDigits;
    }

    private function resolveUrl(): string
    {
        $override = trim((string) $this->config('url', ''));

        if ($override !== '') {
            return $this->normalizeLegacyUrl($override);
        }

        $baseUrl = rtrim((string) $this->config('base_url', 'https://app.nextsms.co.tz'), '/');
        $path = (bool) $this->config('test_mode', false)
            ? (string) $this->config('test_endpoint', '/api/sms/v1/text/single')
            : (string) $this->config('endpoint', '/api/sms/v1/text/single');

        return $baseUrl.'/'.ltrim($path, '/');
    }

    /**
     * api.nextsms.com/api/v1/sms/send is deprecated and returns HTTP 404.
     */
    private function normalizeLegacyUrl(string $url): string
    {
        $lower = strtolower($url);

        if (str_contains($lower, 'api.nextsms.com') && str_contains($lower, '/api/v1/sms/send')) {
            Log::notice('NextSMS legacy URL detected; using app.nextsms.co.tz/api/sms/v1/text/single instead.', [
                'configured_url' => $url,
            ]);

            return 'https://app.nextsms.co.tz/api/sms/v1/text/single';
        }

        return $url;
    }

    /**
     * @param  array<string, mixed>|null  $body
     * @return array{success: bool, provider_response: string}
     */
    private function parseResponse(int $status, string $rawBody, ?array $body, string $to): array
    {
        $providerResponse = mb_substr($rawBody, 0, 500);

        if ($status < 200 || $status >= 300) {
            Log::warning('NextSMS HTTP failed', [
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
            Log::warning('NextSMS returned non-success status in body', [
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
        if (isset($body['success']) && $body['success'] === false) {
            return false;
        }

        if (isset($body['status']) && is_string($body['status'])) {
            $status = strtolower($body['status']);

            if (in_array($status, ['error', 'failed', 'failure'], true)) {
                return false;
            }

            if ($status === 'success') {
                return true;
            }
        }

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

        $groupId = (int) ($status['groupId'] ?? 0);
        $groupName = strtoupper((string) ($status['groupName'] ?? ''));
        $statusName = strtoupper((string) ($status['name'] ?? ''));

        if ($groupId === 5 || in_array($groupName, ['FAILED', 'REJECTED'], true)) {
            return false;
        }

        if (str_contains($statusName, 'REJECTED') || str_contains($statusName, 'FAILED')) {
            return false;
        }

        return true;
    }

    private function config(string $key, mixed $default = null): mixed
    {
        return config('services.sms.nextsms.'.$key, $default);
    }

    /**
     * Resolve Base64(username:password) for Authorization: Basic …
     *
     * Accepts PRMS_SMS_BASIC_AUTH as:
     * - raw base64 (e.g. am9obkBleGFtcGxlLmNvbTphYmMxMjM=)
     * - full header (Authorization: Basic am9h…)
     * - or username + password from env (encoded automatically)
     */
    private function resolveBasicCredentials(): string
    {
        $raw = trim((string) $this->config('basic_auth', ''));

        if ($raw !== '') {
            if (preg_match('/^authorization:\s*basic\s+(\S+)/i', $raw, $matches)) {
                return $matches[1];
            }

            if (str_starts_with(strtolower($raw), 'basic ')) {
                return trim(substr($raw, 6));
            }

            return $raw;
        }

        $username = (string) $this->config('username', '');
        $password = (string) $this->config('password', '');

        return base64_encode($username.':'.$password);
    }
}
