<?php

namespace App\Support;

use App\Models\ProjectGroup;
use App\Models\StageDeadline;
use App\Models\StudentEvaluation;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Student-facing academic calendar grouped by submission, presentation,
 * evaluation, and coordinator announcements.
 */
final class PrmsAcademicCalendar
{
    /**
     * @return array{
     *     submission_deadlines: Collection<int, array<string, mixed>>,
     *     presentation_dates: Collection<int, array<string, mixed>>,
     *     evaluation_schedule: Collection<int, array<string, mixed>>,
     *     announcements: Collection<int, array<string, mixed>>
     * }
     */
    public static function forUser(User $user, ?ProjectGroup $projectGroup = null): array
    {
        $now = now();
        $deadlines = self::applicableDeadlines($projectGroup);

        $submissionDeadlines = collect();
        $presentationDates = collect();

        foreach ($deadlines as $deadline) {
            $item = self::formatDeadlineItem($deadline, $now);

            if (self::isPresentationStage($deadline->stage_name)) {
                $presentationDates->push($item);
            } else {
                $submissionDeadlines->push($item);
            }
        }

        foreach (self::globalDeadlineEntries($now) as $entry) {
            $submissionDeadlines->push($entry);
        }

        return [
            'submission_deadlines' => $submissionDeadlines->sortBy('sort_at')->values(),
            'presentation_dates' => $presentationDates->sortBy('sort_at')->values(),
            'evaluation_schedule' => self::evaluationScheduleForUser($user, $projectGroup),
            'announcements' => self::announcements(),
        ];
    }

    /**
     * @return Collection<int, StageDeadline>
     */
    private static function applicableDeadlines(?ProjectGroup $projectGroup): Collection
    {
        return StageDeadline::query()
            ->with('projectGroup')
            ->where(function ($query) use ($projectGroup) {
                $query->whereNull('project_group_id');
                if ($projectGroup) {
                    $query->orWhere('project_group_id', $projectGroup->id);
                }
            })
            ->orderBy('end_time')
            ->get()
            ->unique(fn (StageDeadline $deadline) => $deadline->stage_name.'|'.($deadline->project_group_id ?? 'global'));
    }

    /**
     * @return array<string, mixed>
     */
    private static function formatDeadlineItem(StageDeadline $deadline, Carbon $now): array
    {
        $start = $deadline->start_time;
        $end = $deadline->end_time;
        $status = self::deadlineStatus($start, $end, $now);

        return [
            'label' => StudentStageProgress::shortStageLabel($deadline->stage_name),
            'stage_name' => $deadline->stage_name,
            'start' => $start,
            'end' => $end,
            'window' => self::formatWindow($start, $end),
            'status' => $status,
            'status_label' => self::statusLabel($status),
            'scope' => $deadline->projectGroup?->name ?? 'All students',
            'academic_year' => $deadline->academic_year,
            'sort_at' => optional($end ?? $start)->timestamp ?? PHP_INT_MAX,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function globalDeadlineEntries(Carbon $now): array
    {
        $configs = SystemConfiguration::query()
            ->whereIn('config_key', ['deadline_proposal', 'deadline_final', 'academic_year'])
            ->pluck('config_value', 'config_key');

        $entries = [];

        foreach ([
            'deadline_proposal' => 'Proposal submission deadline',
            'deadline_final' => 'Final submission deadline',
        ] as $key => $label) {
            $value = trim((string) ($configs[$key] ?? ''));
            if ($value === '') {
                continue;
            }

            try {
                $date = Carbon::parse($value)->endOfDay();
            } catch (\Throwable) {
                continue;
            }

            $status = self::deadlineStatus(null, $date, $now);

            $entries[] = [
                'label' => $label,
                'stage_name' => $label,
                'start' => null,
                'end' => $date,
                'window' => $date->format('M j, Y'),
                'status' => $status,
                'status_label' => self::statusLabel($status),
                'scope' => 'University-wide',
                'academic_year' => (string) ($configs['academic_year'] ?? ''),
                'sort_at' => $date->timestamp,
            ];
        }

        return $entries;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function evaluationScheduleForUser(User $user, ?ProjectGroup $projectGroup): Collection
    {
        return StudentEvaluation::query()
            ->with(['evaluator', 'submission', 'rubric'])
            ->where('student_id', $user->id)
            ->when($projectGroup, fn ($query) => $query->where(function ($inner) use ($projectGroup) {
                $inner->whereNull('project_group_id')
                    ->orWhere('project_group_id', $projectGroup->id);
            }))
            ->latest()
            ->limit(12)
            ->get()
            ->map(function (StudentEvaluation $evaluation) {
                $submission = $evaluation->submission;
                $stageLabel = $submission
                    ? StudentStageProgress::shortStageLabel((string) $submission->stage)
                    : 'Presentation evaluation';

                return [
                    'label' => $stageLabel,
                    'evaluator' => $evaluation->evaluator?->name ?? 'Supervisor',
                    'status' => $evaluation->status ?? 'pending',
                    'status_label' => self::evaluationStatusLabel((string) ($evaluation->status ?? 'pending')),
                    'score' => $evaluation->total_score,
                    'scope' => $evaluation->evaluation_scope ?? 'presentation',
                    'sort_at' => optional($evaluation->updated_at ?? $evaluation->created_at)->timestamp ?? 0,
                    'when' => optional($evaluation->updated_at ?? $evaluation->created_at)->format('M j, Y'),
                ];
            })
            ->sortByDesc('sort_at')
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function announcements(): Collection
    {
        $raw = trim((string) SystemConfiguration::query()
            ->where('config_key', 'calendar_announcements')
            ->value('config_value'));

        if ($raw === '') {
            return collect();
        }

        if (str_starts_with($raw, '[')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return collect($decoded)
                    ->filter(fn ($row) => is_array($row) && trim((string) ($row['title'] ?? $row['body'] ?? '')) !== '')
                    ->map(fn (array $row) => [
                        'title' => trim((string) ($row['title'] ?? 'Announcement')),
                        'body' => trim((string) ($row['body'] ?? '')),
                        'date' => trim((string) ($row['date'] ?? '')),
                        'sort_at' => self::announcementSortKey((string) ($row['date'] ?? '')),
                    ])
                    ->sortByDesc('sort_at')
                    ->values();
            }
        }

        return collect(preg_split('/\r\n|\r|\n/', $raw) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->map(function (string $line) {
                $date = '';
                $title = $line;
                $body = '';

                if (preg_match('/^(\d{4}-\d{2}-\d{2})\s*\|\s*(.+)$/', $line, $matches)) {
                    $date = $matches[1];
                    $line = trim($matches[2]);
                }

                if (str_contains($line, ' — ')) {
                    [$title, $body] = array_map('trim', explode(' — ', $line, 2));
                } elseif (str_contains($line, ' - ')) {
                    [$title, $body] = array_map('trim', explode(' - ', $line, 2));
                }

                return [
                    'title' => $title,
                    'body' => $body,
                    'date' => $date,
                    'sort_at' => self::announcementSortKey($date),
                ];
            })
            ->sortByDesc('sort_at')
            ->values();
    }

    private static function isPresentationStage(string $stageName): bool
    {
        $normalized = strtolower(trim($stageName));

        return str_contains($normalized, 'presentation');
    }

    private static function deadlineStatus(?Carbon $start, ?Carbon $end, Carbon $now): string
    {
        if ($end && $now->isAfter($end)) {
            return 'closed';
        }

        if ($start && $now->isBefore($start)) {
            return 'upcoming';
        }

        return 'active';
    }

    private static function statusLabel(string $status): string
    {
        return match ($status) {
            'closed' => 'Closed',
            'upcoming' => 'Upcoming',
            default => 'Open',
        };
    }

    private static function evaluationStatusLabel(string $status): string
    {
        return match ($status) {
            'finalized', 'approved', 'completed' => 'Completed',
            'draft' => 'In progress',
            default => 'Scheduled',
        };
    }

    private static function formatWindow(?Carbon $start, ?Carbon $end): string
    {
        if ($start && $end) {
            return $start->format('M j, Y H:i').' – '.$end->format('M j, Y H:i');
        }

        if ($end) {
            return 'Due '.$end->format('M j, Y H:i');
        }

        if ($start) {
            return 'From '.$start->format('M j, Y H:i');
        }

        return 'Date to be announced';
    }

    private static function announcementSortKey(string $date): int
    {
        if ($date === '') {
            return 0;
        }

        try {
            return Carbon::parse($date)->timestamp;
        } catch (\Throwable) {
            return 0;
        }
    }
}
