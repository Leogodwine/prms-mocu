<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\PrmsNavigationIndex;
use Database\Seeders\ProjectStageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrmsNavigationIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_coordinator_sidebar_includes_groups_hub(): void
    {
        $user = User::factory()->coordinator()->create();

        $labels = collect(PrmsNavigationIndex::sidebarForUser($user))->pluck('label');

        $this->assertTrue($labels->contains('Groups & assignments'));
        $this->assertTrue($labels->contains('Deadlines'));
    }

    public function test_supervisor_quick_nav_includes_review_queue(): void
    {
        $user = User::factory()->supervisor()->create();

        $labels = collect(PrmsNavigationIndex::quickNavForUser($user))->pluck('label');

        $this->assertTrue($labels->contains('Review submissions'));
        $this->assertTrue($labels->contains('Assigned students'));
    }

    public function test_student_quick_nav_includes_workspace_chapters(): void
    {
        $this->seed(ProjectStageSeeder::class);

        $user = User::factory()->student('research_student')->create();

        $items = collect(PrmsNavigationIndex::quickNavForUser($user));

        $this->assertTrue($items->pluck('label')->contains('Chapter 1'));
        $this->assertTrue(
            $items->contains(fn (array $item) => ($item['label'] ?? '') === 'Chapter 1'
                && ($item['subtitle'] ?? '') === 'Research proposal')
        );
    }

    public function test_students_do_not_see_coordinator_pages(): void
    {
        $user = User::factory()->student('project_student')->create();

        $labels = collect(PrmsNavigationIndex::forUser($user))->pluck('label');

        $this->assertFalse($labels->contains('Groups & assignments'));
        $this->assertTrue($labels->contains('New project/proposal creation'));
    }

    public function test_student_sidebar_starts_with_dashboard_and_ends_with_notifications(): void
    {
        $user = User::factory()->student('research_student')->create();

        $labels = collect(PrmsNavigationIndex::sidebarForUser($user))->pluck('label')->values();

        $this->assertSame('Dashboard', $labels->first());
        $this->assertSame('Notifications', $labels->last());
        $this->assertSame(
            [
                'Dashboard',
                'New project/proposal creation',
                'Research proposal',
                'Research report',
                'Public repository',
                'Notifications',
            ],
            $labels->all()
        );
    }

    public function test_student_workspace_sidebar_items_include_chapter_children(): void
    {
        $this->seed(ProjectStageSeeder::class);

        $user = User::factory()->student('research_student')->create();

        $proposal = collect(PrmsNavigationIndex::sidebarForUser($user))
            ->firstWhere('label', 'Research proposal');

        $this->assertNotNull($proposal);
        $this->assertCount(3, $proposal['children'] ?? []);
        $this->assertSame('Chapter 1', $proposal['children'][0]['label'] ?? null);
        $this->assertStringContainsString('type=proposal', $proposal['children'][0]['url'] ?? '');
        $this->assertStringContainsString('stage_id=', $proposal['children'][0]['url'] ?? '');
    }
}
