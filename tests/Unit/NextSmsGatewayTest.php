<?php

namespace Tests\Unit;

use App\Services\Sms\NextSmsGateway;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NextSmsGatewayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.sms.nextsms', [
            'base_url' => 'https://app.nextsms.co.tz',
            'endpoint' => '/api/sms/v1/text/single',
            'test_endpoint' => '/api/sms/v1/text/single',
            'test_mode' => false,
            'url' => '',
            'auth' => 'bearer',
            'token' => 'test-bearer-token',
            'username' => '',
            'password' => '',
            'basic_auth' => '',
            'sender_id' => 'MoCU-PRMS',
            'callback_url' => null,
            'payload_format' => 'from_to_text',
            'use_local_mobile_format' => false,
            'timeout' => 15,
        ]);
    }

    public function test_sends_official_nextsms_from_to_text_payload(): void
    {
        Http::fake([
            'app.nextsms.co.tz/*' => Http::response([
                'messages' => [
                    [
                        'to' => '255712345678',
                        'status' => [
                            'groupId' => 18,
                            'groupName' => 'PENDING',
                            'name' => 'ENROUTE (SENT)',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $gateway = new NextSmsGateway;
        $result = $gateway->send('+255712345678', 'Hello from PRMS');

        $this->assertTrue($result['success']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://app.nextsms.co.tz/api/sms/v1/text/single'
                && $request->hasHeader('Authorization', 'Bearer test-bearer-token')
                && $request['from'] === 'MoCU-PRMS'
                && $request['to'] === '255712345678'
                && $request['text'] === 'Hello from PRMS'
                && str_starts_with((string) $request['reference'], 'prms-');
        });
    }

    public function test_auto_payload_uses_sender_mobile_message_for_send_endpoint(): void
    {
        Config::set('services.sms.nextsms.url', 'https://example.test/api/v1/sms/send');
        Config::set('services.sms.nextsms.payload_format', 'auto');

        Http::fake([
            'example.test/*' => Http::response([
                'status' => 'success',
                'message_id' => 'abcd1234',
            ], 200),
        ]);

        $gateway = new NextSmsGateway;
        $result = $gateway->send('0712345678', 'Hello from PRMS');

        $this->assertTrue($result['success']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.test/api/v1/sms/send'
                && $request['sender_id'] === 'MoCU-PRMS'
                && $request['mobile'] === '255712345678'
                && $request['message'] === 'Hello from PRMS';
        });
    }

    public function test_normalizes_deprecated_api_nextsms_com_url(): void
    {
        Config::set('services.sms.nextsms.url', 'http://api.nextsms.com/api/v1/sms/send');
        Config::set('services.sms.nextsms.payload_format', 'from_to_text');

        Http::fake([
            'app.nextsms.co.tz/*' => Http::response([
                'messages' => [
                    ['status' => ['groupName' => 'PENDING', 'name' => 'ENROUTE (SENT)']],
                ],
            ], 200),
        ]);

        $gateway = new NextSmsGateway;
        $result = $gateway->send('255712345678', 'Hello');

        $this->assertTrue($result['success']);
        Http::assertSent(fn ($request) => $request->url() === 'https://app.nextsms.co.tz/api/sms/v1/text/single'
            && $request['from'] === 'MoCU-PRMS');
    }

    public function test_supports_basic_authentication(): void
    {
        Config::set('services.sms.nextsms.auth', 'basic');
        Config::set('services.sms.nextsms.token', '');
        Config::set('services.sms.nextsms.username', 'Aladdin');
        Config::set('services.sms.nextsms.password', 'open sesame');

        Http::fake([
            'app.nextsms.co.tz/*' => Http::response([
                'messages' => [
                    ['status' => ['groupName' => 'PENDING', 'name' => 'ENROUTE (SENT)']],
                ],
            ], 200),
        ]);

        $gateway = new NextSmsGateway;
        $gateway->send('255712345678', 'Test');

        Http::assertSent(fn ($request) => $request->hasHeader(
            'Authorization',
            'Basic '.base64_encode('Aladdin:open sesame')
        ));
    }

    public function test_accepts_pre_encoded_basic_auth_header(): void
    {
        Config::set('services.sms.nextsms.auth', 'basic');
        Config::set('services.sms.nextsms.token', '');
        Config::set('services.sms.nextsms.username', '');
        Config::set('services.sms.nextsms.password', '');
        Config::set('services.sms.nextsms.basic_auth', 'Authorization: Basic '.base64_encode('john@example.com:abc123'));

        Http::fake([
            'app.nextsms.co.tz/*' => Http::response([
                'messages' => [
                    ['status' => ['groupName' => 'PENDING', 'name' => 'ENROUTE (SENT)']],
                ],
            ], 200),
        ]);

        $gateway = new NextSmsGateway;
        $gateway->send('255712345678', 'Test');

        Http::assertSent(fn ($request) => $request->hasHeader(
            'Authorization',
            'Basic '.base64_encode('john@example.com:abc123')
        ));
    }

    public function test_treats_rejected_provider_status_as_failure(): void
    {
        Http::fake([
            'app.nextsms.co.tz/*' => Http::response([
                'messages' => [
                    [
                        'status' => [
                            'groupId' => 5,
                            'groupName' => 'REJECTED',
                            'name' => 'REJECTED_SOURCE',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $gateway = new NextSmsGateway;
        $result = $gateway->send('255712345678', 'Test');

        $this->assertFalse($result['success']);
    }
}
