<?php

namespace Tests\Unit;

use App\Support\PrmsSmsStatus;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PrmsSmsStatusTest extends TestCase
{
    public function test_reports_disabled_state(): void
    {
        Config::set('prms.sms.enabled', false);
        Config::set('prms.sms.driver', 'log');

        $status = PrmsSmsStatus::summary();

        $this->assertFalse($status['enabled']);
        $this->assertTrue($status['ok']);
        $this->assertStringContainsString('Disabled', $status['detail']);
    }

    public function test_reports_missing_nextsms_token_as_not_configured(): void
    {
        Config::set('prms.sms.enabled', true);
        Config::set('prms.sms.driver', 'http');
        Config::set('prms.sms.provider', 'nextsms');
        Config::set('services.sms.nextsms', [
            'base_url' => 'https://app.nextsms.co.tz',
            'endpoint' => '/api/sms/v1/text/single',
            'test_endpoint' => '/api/sms/v1/text/single',
            'test_mode' => false,
            'url' => '',
            'auth' => 'bearer',
            'token' => '',
            'username' => '',
            'password' => '',
            'basic_auth' => '',
            'sender_id' => 'MoCU-PRMS',
        ]);

        $status = PrmsSmsStatus::summary();

        $this->assertFalse($status['configured']);
        $this->assertFalse($status['ok']);
        $this->assertStringContainsString('credentials', strtolower($status['detail']));
    }

    public function test_reports_nextsms_test_mode_when_enabled(): void
    {
        Config::set('prms.sms.enabled', true);
        Config::set('prms.sms.driver', 'http');
        Config::set('prms.sms.provider', 'nextsms');
        Config::set('services.sms.nextsms', [
            'base_url' => 'https://app.nextsms.co.tz',
            'endpoint' => '/api/sms/v1/text/single',
            'test_endpoint' => '/api/sms/v1/text/single',
            'test_mode' => true,
            'url' => '',
            'auth' => 'bearer',
            'token' => 'test-token',
            'sender_id' => 'MoCU-PRMS',
        ]);

        $status = PrmsSmsStatus::summary();

        $this->assertTrue($status['configured']);
        $this->assertTrue($status['test_mode']);
        $this->assertStringContainsString('NextSMS', $status['detail']);
    }
}
