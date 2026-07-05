<?php

namespace Tests\Unit;

use App\Models\ProjectGroup;
use App\Models\StageDeadline;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Support\PrmsAcademicCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrmsAcademicCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_groups_deadlines_presentations_and_announcements(): void
    {
        $student = User::factory()->student()->create();
        $group = ProjectGroup::factory()->create();

        StageDeadline::query()->create([
            'stage_name' => 'Proposal Chapter 1',
            'academic_year' => '2025/2026',
            'project_group_id' => null,
            'start_time' => now()->subDay(),
            'end_time' => now()->addWeek(),
        ]);

        StageDeadline::query()->create([
            'stage_name' => 'Progress Presentation 1',
            'academic_year' => '2025/2026',
            'project_group_id' => $group->id,
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(3),
        ]);

        SystemConfiguration::query()->updateOrCreate(
            ['config_key' => 'deadline_proposal'],
            ['config_value' => now()->addMonth()->toDateString(), 'config_type' => 'string', 'category' => 'deadlines']
        );

        SystemConfiguration::query()->updateOrCreate(
            ['config_key' => 'calendar_announcements'],
            ['config_value' => "2026-07-01 | Faculty briefing — Attend the mandatory orientation session.", 'config_type' => 'string', 'category' => 'calendar']
        );

        $calendar = PrmsAcademicCalendar::forUser($student, $group);

        $this->assertCount(2, $calendar['submission_deadlines']);
        $this->assertSame('Proposal Chapter 1', $calendar['submission_deadlines'][0]['stage_name']);
        $this->assertCount(1, $calendar['presentation_dates']);
        $this->assertSame('Progress Presentation 1', $calendar['presentation_dates'][0]['stage_name']);
        $this->assertCount(1, $calendar['announcements']);
        $this->assertSame('Faculty briefing', $calendar['announcements'][0]['title']);
    }
}
