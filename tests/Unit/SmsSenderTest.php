<?php

namespace Tests\Unit;

use App\Models\SmsDeliveryLog;
use App\Models\User;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\WorkflowNotification;
use App\Services\Sms\SmsSender;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SmsSenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('queue.default', 'sync');

        Schema::dropAllTables();
        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('sms_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 20);
            $table->string('message', 500);
            $table->string('status', 20);
            $table->text('provider_response')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function test_sender_logs_delivery_when_sms_is_disabled(): void
    {
        Config::set('prms.sms.enabled', false);
        Config::set('prms.sms.driver', 'log');

        $sender = app(SmsSender::class);
        $result = $sender->sendSync('+255712345678', 'Test message', null);

        $this->assertTrue($result);
        $this->assertDatabaseHas('sms_delivery_logs', [
            'phone' => '+255712345678',
            'status' => 'skipped',
        ]);
    }

    public function test_sms_channel_sends_via_sender_when_notification_supports_sms(): void
    {
        Config::set('prms.sms.enabled', true);
        Config::set('prms.sms.driver', 'log');

        $user = new User([
            'id' => 1,
            'phone_number' => '0712345678',
            'notify_sms_workflow' => true,
            'role' => 'project_student',
        ]);

        $notification = new WorkflowNotification(
            'Test title',
            'Test message body',
            'https://example.test/student',
            'Open workspace',
            'info'
        );

        $channel = app(SmsChannel::class);
        $channel->send($user, $notification);

        $this->assertSame(1, SmsDeliveryLog::query()->count());
        $this->assertDatabaseHas('sms_delivery_logs', [
            'phone' => '+255712345678',
            'status' => 'sent',
        ]);
    }
}
