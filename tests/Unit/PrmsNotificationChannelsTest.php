<?php

namespace Tests\Unit;

use App\Models\User;
use App\Notifications\Channels\SmsChannel;
use App\Support\PrmsNotificationChannels;
use Tests\TestCase;

class PrmsNotificationChannelsTest extends TestCase
{
    private function makeUser(array $attributes = []): User
    {
        return new User(array_merge([
            'role' => 'project_student',
            'email' => 'student@example.test',
            'notify_email_workflow' => false,
            'notify_email_new_submission' => false,
            'notify_email_submission_reviewed' => false,
            'notify_sms_workflow' => false,
        ], $attributes));
    }

    public function test_workflow_includes_sms_when_preference_and_phone_are_set(): void
    {
        $user = $this->makeUser([
            'phone_number' => '0712345678',
            'notify_sms_workflow' => true,
        ]);

        $channels = PrmsNotificationChannels::workflow($user);

        $this->assertContains('database', $channels);
        $this->assertContains(SmsChannel::class, $channels);
        $this->assertNotContains('mail', $channels);
    }

    public function test_workflow_omits_sms_without_phone(): void
    {
        $user = $this->makeUser([
            'phone_number' => null,
            'notify_sms_workflow' => true,
        ]);

        $channels = PrmsNotificationChannels::workflow($user);

        $this->assertNotContains(SmsChannel::class, $channels);
    }

    public function test_submission_alert_includes_sms_channel(): void
    {
        $user = $this->makeUser([
            'role' => 'supervisor',
            'phone_number' => '0712345678',
            'notify_sms_workflow' => true,
        ]);

        $channels = PrmsNotificationChannels::submissionAlert($user);

        $this->assertContains(SmsChannel::class, $channels);
    }

    public function test_review_alert_includes_sms_channel(): void
    {
        $user = $this->makeUser([
            'phone_number' => '0712345678',
            'notify_sms_workflow' => true,
        ]);

        $channels = PrmsNotificationChannels::reviewAlert($user);

        $this->assertContains(SmsChannel::class, $channels);
    }

    public function test_phone_for_normalizes_number(): void
    {
        $user = $this->makeUser([
            'phone_number' => '0712345678',
        ]);

        $this->assertSame('+255712345678', PrmsNotificationChannels::phoneFor($user));
    }
}
