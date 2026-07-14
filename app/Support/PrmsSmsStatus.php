<?php

namespace App\Support;

final class PrmsSmsStatus
{
    /**
     * @return array{
     *     enabled: bool,
     *     driver: string,
     *     provider: string,
     *     configured: bool,
     *     sender_id: string,
     *     ok: bool,
     *     detail: string,
     *     test_mode: bool,
     *     url: string
     * }
     */
    public static function summary(): array
    {
        $enabled = (bool) config('prms.sms.enabled', false);
        $driver = (string) config('prms.sms.driver', 'log');
        $provider = (string) config('prms.sms.provider', 'nextsms');
        $smsConfig = config('services.sms.'.$provider, []);
        $senderId = (string) ($smsConfig['sender_id'] ?? 'MoCU-PRMS');
        $testMode = (bool) ($smsConfig['test_mode'] ?? false);
        $url = self::resolveDisplayUrl($smsConfig);
        $hasCredentials = self::hasCredentials($provider, $smsConfig);

        $configured = match ($driver) {
            'http' => $url !== '' && $hasCredentials,
            default => true,
        };

        $ok = ! $enabled || $configured;

        $providerLabel = match ($provider) {
            'messaging_service' => 'Messaging Service API V2',
            default => 'NextSMS',
        };

        $detail = match (true) {
            ! $enabled => 'Disabled (log-only preview)',
            $driver === 'http' && ! $hasCredentials => 'HTTP driver missing NextSMS credentials (set PRMS_SMS_USERNAME and PRMS_SMS_PASSWORD, or PRMS_SMS_BASIC_AUTH)',
            $driver === 'http' && $url === '' => 'HTTP driver missing endpoint URL',
            $driver === 'http' && $testMode => $providerLabel.' test mode',
            $driver === 'http' => $providerLabel.' configured',
            default => 'Log driver (development)',
        };

        return [
            'enabled' => $enabled,
            'driver' => $driver,
            'provider' => $provider,
            'configured' => $configured,
            'sender_id' => $senderId,
            'ok' => $ok,
            'detail' => $detail,
            'test_mode' => $testMode,
            'url' => $url,
        ];
    }

    /**
     * @param  array<string, mixed>  $smsConfig
     */
    private static function hasCredentials(string $provider, array $smsConfig): bool
    {
        if ($provider === 'nextsms') {
            if (trim((string) ($smsConfig['basic_auth'] ?? '')) !== '') {
                return true;
            }

            if (trim((string) ($smsConfig['username'] ?? '')) !== ''
                && trim((string) ($smsConfig['password'] ?? '')) !== '') {
                return true;
            }

            if (strtolower((string) ($smsConfig['auth'] ?? 'basic')) === 'bearer') {
                return trim((string) ($smsConfig['token'] ?? '')) !== '';
            }

            return false;
        }

        return trim((string) ($smsConfig['token'] ?? '')) !== '';
    }

    /**
     * @param  array<string, mixed>  $smsConfig
     */
    private static function resolveDisplayUrl(array $smsConfig): string
    {
        $override = trim((string) ($smsConfig['url'] ?? ''));

        if ($override !== '') {
            return $override;
        }

        $baseUrl = rtrim((string) ($smsConfig['base_url'] ?? ''), '/');

        if ($baseUrl === '') {
            return '';
        }

        $path = (bool) ($smsConfig['test_mode'] ?? false)
            ? (string) ($smsConfig['test_endpoint'] ?? '')
            : (string) ($smsConfig['endpoint'] ?? '');

        return $baseUrl.'/'.ltrim($path, '/');
    }
}
