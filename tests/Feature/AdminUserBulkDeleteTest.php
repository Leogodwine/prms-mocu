<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserBulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_bulk_delete_users(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
            'must_change_password' => false,
        ]);

        $target = User::factory()->create([
            'role' => 'supervisor',
            'account_status' => 'active',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.bulk-delete'), [
            'user_ids' => [$target->id],
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('status');
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_admin_users_index_renders_bulk_delete_form(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('admin/users/bulk-delete', false);
    }
}
