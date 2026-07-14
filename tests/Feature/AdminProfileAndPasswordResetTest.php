<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminProfileAndPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_admin_cannot_change_own_name_or_email_via_profile(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->administrator()->create([
            'name' => 'System Administrator',
            'email' => 'admin@mocu.ac.tz',
            'department' => 'ICT',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($admin)->put(route('profile.update'), [
            'phone_number' => '+255700000001',
        ]);

        $response->assertRedirect(route('profile.show'));
        $admin->refresh();
        $this->assertSame('System Administrator', $admin->name);
        $this->assertSame('admin@mocu.ac.tz', $admin->email);
        $this->assertSame('+255700000001', $admin->phone_number);
    }

    public function test_admin_cannot_change_own_name_via_tampered_request(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->administrator()->create([
            'name' => 'System Administrator',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($admin)->from(route('profile.edit'))->put(route('profile.update'), [
            'name' => 'Changed Name',
            'email' => 'changed@mocu.ac.tz',
            'phone_number' => '+255700000001',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHasErrors('name');
        $this->assertSame('System Administrator', $admin->fresh()->name);
    }

    public function test_admin_profile_update_rejects_tampered_locked_fields(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->administrator()->create([
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($admin)->from(route('profile.edit'))->put(route('profile.update'), [
            'name' => 'Tampered Name',
            'phone_number' => '+255700000002',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHasErrors('name');
    }

    public function test_admin_can_reset_another_users_password(): void
    {
        Notification::fake();
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->administrator()->create([
            'must_change_password' => false,
        ]);

        $target = User::factory()->supervisor()->create([
            'must_change_password' => false,
        ]);

        $oldHash = $target->password;

        $response = $this->actingAs($admin)->post(route('admin.users.reset-password', $target));

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $target->refresh();
        $this->assertTrue($target->must_change_password);
        $this->assertNotSame($oldHash, $target->password);

        Notification::assertSentTo($target, AccountCreatedNotification::class);
    }

    public function test_admin_cannot_reset_own_password_from_user_management(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->administrator()->create([
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.reset-password', $admin));

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
    }
}
