<?php

namespace Tests\Unit;

use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Support\PrmsNavigationIndex;
use Database\Seeders\ProjectStageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrmsNavigationIndexTest extends TestCase
{
    use RefreshDatabase;

    private function createEligibleResearchStudent(): User
    {
        $programme = Program::factory()->create([
            'final_year' => 3,
            'duration_years' => 3,
            'output_type' => 'RESEARCH_ONLY',
            'academic_level' => 'bachelor',
        ]);

        $user = User::factory()->student()->create([
            'year_of_study' => 3,
        ]);

        Student::factory()->create([
            'user_id' => $user->id,
            'programme_id' => $programme->id,
            'year_of_study' => 3,
            'enrollment_status' => 'active',
        ]);

        return $user->fresh();
    }

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

        $user = $this->createEligibleResearchStudent();

        $items = collect(PrmsNavigationIndex::quickNavForUser($user));

        $this->assertTrue($items->pluck('label')->contains('Chapter 1'));
        $this->assertTrue(
            $items->contains(fn (array $item) => ($item['label'] ?? '') === 'Chapter 1'
                && strcasecmp((string) ($item['subtitle'] ?? ''), 'Research Proposal') === 0)
        );
    }

    public function test_students_do_not_see_coordinator_pages(): void
    {
        $user = $this->createEligibleResearchStudent();

        $labels = collect(PrmsNavigationIndex::forUser($user))->pluck('label');

        $this->assertFalse($labels->contains('Groups & assignments'));
        $this->assertTrue($labels->contains('Register project idea'));
        $this->assertSame('student', $user->role);
    }

    public function test_student_sidebar_starts_with_dashboard_and_ends_with_notifications(): void
    {
        $user = $this->createEligibleResearchStudent();

        $labels = collect(PrmsNavigationIndex::sidebarForUser($user))->pluck('label')->values();

        $this->assertSame('Dashboard', $labels->first());
        $this->assertSame('Notifications', $labels->last());
        $this->assertSame(
            [
                'Dashboard',
                'Register project idea',
                'Research Proposal',
                'Research Report',
                'Public Repository',
                'Notifications',
            ],
            $labels->all()
        );
    }

    public function test_student_workspace_sidebar_items_include_chapter_children(): void
    {
        $this->seed(ProjectStageSeeder::class);

        $user = $this->createEligibleResearchStudent();

        $proposal = collect(PrmsNavigationIndex::sidebarForUser($user))
            ->firstWhere('label', 'Research Proposal');

        $this->assertNotNull($proposal);
        $this->assertGreaterThanOrEqual(3, count($proposal['children'] ?? []));
        $this->assertSame('Chapter 1', $proposal['children'][0]['label'] ?? null);
        $this->assertStringContainsString('type=proposal', $proposal['children'][0]['url'] ?? '');
        $this->assertStringContainsString('stage_id=', $proposal['children'][0]['url'] ?? '');
    }

    public function test_ineligible_students_do_not_see_workspace_nav_items(): void
    {
        $programme = Program::factory()->create([
            'final_year' => 3,
            'output_type' => 'NONE',
            'academic_level' => 'certificate',
        ]);

        $user = User::factory()->student()->create([
            'year_of_study' => 1,
        ]);

        Student::factory()->create([
            'user_id' => $user->id,
            'programme_id' => $programme->id,
            'year_of_study' => 1,
            'enrollment_status' => 'active',
            'academic_level' => 'certificate',
        ]);

        $labels = collect(PrmsNavigationIndex::sidebarForUser($user->fresh()))->pluck('label')->values();

        $this->assertSame(
            ['Dashboard', 'Public Repository', 'Notifications'],
            $labels->all()
        );
        $this->assertSame('student', $user->fresh()->role);
    }
}
