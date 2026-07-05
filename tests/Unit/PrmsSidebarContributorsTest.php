<?php

namespace Tests\Unit;

use App\Models\ProjectGroup;
use App\Models\User;
use App\Support\PrmsSidebarContributors;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrmsSidebarContributorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_null_for_non_students(): void
    {
        $user = User::factory()->supervisor()->create();

        $this->assertNull(PrmsSidebarContributors::panelForUser($user));
    }

    public function test_returns_null_when_student_has_no_group(): void
    {
        $user = User::factory()->student('project_student')->create();

        $this->assertNull(PrmsSidebarContributors::panelForUser($user));
    }

    public function test_returns_null_when_group_has_only_one_member(): void
    {
        $user = User::factory()->student('project_student')->create();
        $group = ProjectGroup::factory()->create();
        $group->members()->sync([$user->id]);

        $this->assertNull(PrmsSidebarContributors::panelForUser($user));
    }

    public function test_returns_group_members_for_student_in_multi_member_group(): void
    {
        $user = User::factory()->student('project_student')->create();
        $peer = User::factory()->student('project_student')->create();
        $group = ProjectGroup::factory()->create(['name' => 'Team Alpha']);
        $group->members()->sync([$user->id, $peer->id]);

        $panel = PrmsSidebarContributors::panelForUser($user->fresh());

        $this->assertNotNull($panel);
        $this->assertSame('Team Alpha', $panel['group_name']);
        $this->assertCount(2, $panel['members']);
        $this->assertTrue($panel['members']->pluck('id')->contains($user->id));
        $this->assertTrue($panel['members']->pluck('id')->contains($peer->id));
    }
}
