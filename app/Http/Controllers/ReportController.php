<?php

namespace App\Http\Controllers;

use App\Models\ProjectSubmission;
use App\Models\SupervisorAssignment;
use App\Support\PrmsListFilters;
use App\Models\User;
use App\Support\CoordinatorReportScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * FR-12 — Reports & analytics.
 *
 * Builds statistical aggregates (status mix, stage breakdown, monthly
 * throughput) over project_submissions for the coordinator and
 * supervisor dashboards. Supervisor scope matches the review queue
 * (group assignments and individual student assignments). The view layer
 * renders the aggregates as Chart.js charts on top of the existing KPI cards.
 *
 * Aggregates are produced in PHP from a single SELECT per scope so we
 * avoid N database round-trips, keep the page snappy, and stay
 * compatible with both MySQL and SQLite (no DATE_FORMAT dependency).
 */
class ReportController extends Controller
{
    /** Submission statuses, ordered for stable chart legends. */
    private const STATUS_ORDER = [
        'submitted',
        'under_review',
        'approved',
        'rejected',
        'needs_revision',
        'pending',
    ];

    /** Hex colours for the status doughnut, paired with STATUS_ORDER. */
    private const STATUS_COLOURS = [
        'submitted'      => '#1f47b8',
        'under_review'   => '#0f7eb2',
        'approved'       => '#0f9d58',
        'rejected'       => '#c43c3c',
        'needs_revision' => '#c98306',
        'pending'        => '#6c7383',
    ];

    public function coordinator(Request $request): View
    {
        $user = $request->user();

        $groupIds = CoordinatorReportScope::projectGroupIdsForUser($user);

        $base = ProjectSubmission::query()->whereIn('project_group_id', $groupIds);

        $stats = $this->coreCounts($base);

        $assignedSupervisors = SupervisorAssignment::query()
            ->whereIn('project_group_id', $groupIds)
            ->distinct('supervisor_id')
            ->count('supervisor_id');

        $statusMix    = $this->statusMix(clone $base);
        $stageMix     = $this->stageMix(clone $base);
        $monthlyTrend = $this->monthlyTrend(clone $base);

        [$reportEyebrow, $reportLead] = match ($user->role) {
            'admin' => [
                'Administration',
                'Throughput, status mix and stage progress across all project groups.',
            ],
            'hod' => [
                'Department',
                'Throughput and status mix for groups that include students from your department (same scope as the HoD dashboard).',
            ],
            default => [
                'Coordinator',
                'Throughput, status mix and stage progress for the project groups under your coordination.',
            ],
        };

        return view('reports.coordinator', [
            'reportEyebrow'       => $reportEyebrow,
            'reportLead'          => $reportLead,
            'totalGroups'         => $groupIds->count(),
            'totalSubmissions'    => $stats['total'],
            'approvedSubmissions' => $stats['approved'],
            'pendingSubmissions'  => $stats['pending'],
            'rejectedSubmissions' => $stats['rejected'],
            'reviewedSubmissions' => $stats['reviewed'],
            'assignedSupervisors' => $assignedSupervisors,
            'statusMix'           => $statusMix,
            'stageMix'            => $stageMix,
            'monthlyTrend'        => $monthlyTrend,
        ]);
    }

    public function coordinatorExport(Request $request): StreamedResponse
    {
        $user = $request->user();

        $groupIds = CoordinatorReportScope::projectGroupIdsForUser($user);

        return $this->streamSubmissionsCsv(
            ProjectSubmission::query()
                ->with('projectGroup')
                ->whereIn('project_group_id', $groupIds)
                ->orderByDesc('submitted_at')
                ->get(),
            'coordinator-report.csv'
        );
    }

    /**
     * Drill-down list for coordinator-style analytics — same project-group scope
     * as {@see self::coordinator()} with optional status filter matching KPI semantics.
     */
    public function coordinatorMaterials(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        $defaults = ['status' => 'all'];

        if ($request->filled('apply_status')) {
            $status = (string) $request->query('apply_status');
            session()->flash(PrmsListFilters::sessionKey('reports.coordinator.materials'), [
                'status' => match ($status) {
                    'pending', 'approved', 'rejected', 'reviewed', 'all' => $status,
                    default => 'all',
                },
            ]);

            return redirect()->route('reports.coordinator.materials');
        }

        $resolved = PrmsListFilters::resolve(
            $request,
            'reports.coordinator.materials',
            $defaults,
            'reports.coordinator.materials',
            [],
            fn (array $filters) => [
                'status' => match ($filters['status'] ?? 'all') {
                    'pending', 'approved', 'rejected', 'reviewed', 'all' => $filters['status'],
                    default => 'all',
                },
            ]
        );

        if ($resolved['redirect'] !== null) {
            return $resolved['redirect'];
        }

        $status = $resolved['filters']['status'];

        $query = $this->coordinatorMaterialsQuery($user, $status);
        $submissions = $query->latest('submitted_at')->paginate(20);

        return view('reports.coordinator-materials', [
            'submissions' => $submissions,
            'statusFilter' => $status,
            'filters' => $resolved['filters'],
            'filterResetUrl' => PrmsListFilters::resetUrl('reports.coordinator.materials'),
        ]);
    }

    public function supervisor(Request $request): View
    {
        $user = $request->user();

        $assignedGroupIds = SupervisorAssignment::query()
            ->where('supervisor_id', $user->id)
            ->whereNotNull('project_group_id')
            ->pluck('project_group_id');

        $base = $this->supervisorReportSubmissionsQuery($user);

        $stats = $this->coreCounts($base);

        $statusMix    = $this->statusMix(clone $base);
        $stageMix     = $this->stageMix(clone $base);
        $monthlyTrend = $this->monthlyTrend(clone $base);

        return view('reports.supervisor', [
            'totalAssignedGroups' => $assignedGroupIds->count(),
            'totalReviewed'       => $stats['reviewed'],
            'totalPending'        => $stats['pending'],
            'totalApproved'       => $stats['approved'],
            'totalRejected'       => $stats['rejected'],
            'totalSubmissions'    => $stats['total'],
            'statusMix'           => $statusMix,
            'stageMix'            => $stageMix,
            'monthlyTrend'        => $monthlyTrend,
        ]);
    }

    public function supervisorExport(Request $request): StreamedResponse
    {
        $user = $request->user();

        return $this->streamSubmissionsCsv(
            $this->supervisorReportSubmissionsQuery($user)
                ->with(['projectGroup', 'student'])
                ->orderByDesc('submitted_at')
                ->get(),
            'supervisor-report.csv'
        );
    }

    /**
     * Same submission scope as {@see SupervisorController::index()}:
     * assigned via project group supervisor, or via individual student assignment.
     */
    private function supervisorReportSubmissionsQuery(User $user): Builder
    {
        $supervisorId = $user->id;

        return ProjectSubmission::query()
            ->where(function ($query) use ($supervisorId) {
                $query->whereHas('projectGroup.supervisorAssignment', fn ($q) => $q->where('supervisor_id', $supervisorId))
                    ->orWhereHas('student.studentAssignment', fn ($q) => $q->where('supervisor_id', $supervisorId));
            });
    }

    private function coordinatorMaterialsQuery(User $user, string $status): Builder
    {
        $groupIds = CoordinatorReportScope::projectGroupIdsForUser($user);

        $q = ProjectSubmission::query()
            ->with(['projectGroup', 'student'])
            ->whereIn('project_group_id', $groupIds);

        if ($status === 'pending') {
            $q->whereRaw(
                'LOWER(COALESCE(status, "")) IN (?,?,?,?)',
                ['pending', 'submitted', 'under_review', 'needs_revision']
            );
        } elseif ($status === 'approved') {
            $q->whereRaw('LOWER(COALESCE(status, "")) = ?', ['approved']);
        } elseif ($status === 'rejected') {
            $q->whereRaw('LOWER(COALESCE(status, "")) = ?', ['rejected']);
        } elseif ($status === 'reviewed') {
            $q->whereRaw('LOWER(COALESCE(status, "")) IN (?,?)', ['approved', 'rejected']);
        }

        return $q;
    }

    /**
     * Aggregate the headline counts (total / approved / pending / rejected /
     * reviewed) used by the KPI cards.
     */
    private function coreCounts(Builder $base): array
    {
        $rows = (clone $base)
            ->selectRaw('LOWER(status) as status, COUNT(*) as total')
            ->groupBy(DB::raw('LOWER(status)'))
            ->pluck('total', 'status');

        $get = fn (string $key) => (int) ($rows[$key] ?? 0);

        $total    = (int) array_sum($rows->all());
        $approved = $get('approved');
        $rejected = $get('rejected');
        $pending  = $get('pending') + $get('submitted') + $get('under_review') + $get('needs_revision');
        $reviewed = $approved + $rejected;

        return compact('total', 'approved', 'rejected', 'pending', 'reviewed');
    }

    /**
     * Build the status-mix dataset for a doughnut chart.
     * @return array{labels: string[], values: int[], colours: string[]}
     */
    private function statusMix(Builder $base): array
    {
        $rows = $base
            ->selectRaw('LOWER(COALESCE(status, "")) as status, COUNT(*) as total')
            ->groupBy(DB::raw('LOWER(COALESCE(status, ""))'))
            ->pluck('total', 'status');

        $labels = $values = $colours = [];

        foreach (self::STATUS_ORDER as $status) {
            if (! $rows->has($status)) {
                continue;
            }
            $labels[]  = Str::title(str_replace('_', ' ', $status));
            $values[]  = (int) $rows->get($status);
            $colours[] = self::STATUS_COLOURS[$status] ?? '#6c7383';
        }

        // Catch any unknown status labels so we never silently drop rows.
        foreach ($rows as $status => $count) {
            if (in_array($status, self::STATUS_ORDER, true) || $status === '') {
                continue;
            }
            $labels[]  = Str::title(str_replace('_', ' ', (string) $status));
            $values[]  = (int) $count;
            $colours[] = '#99a0ae';
        }

        return ['labels' => $labels, 'values' => $values, 'colours' => $colours];
    }

    /**
     * Build the stage-mix dataset for a horizontal bar chart.
     * Stages are taken from the `stage` column verbatim and prettified
     * for display so reports survive new stage labels appearing in data.
     *
     * @return array{labels: string[], values: int[]}
     */
    private function stageMix(Builder $base): array
    {
        $rows = $base
            ->selectRaw('COALESCE(stage, "Unknown") as stage, COUNT(*) as total')
            ->groupBy('stage')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'stage');

        $labels = $values = [];

        foreach ($rows as $stage => $count) {
            $labels[] = Str::of((string) $stage)
                ->replace('_', ' ')
                ->title()
                ->__toString();
            $values[] = (int) $count;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Monthly throughput for the last 6 months including the current
     * month — separate series for total submissions and approvals.
     *
     * @return array{labels: string[], submitted: int[], approved: int[]}
     */
    private function monthlyTrend(Builder $base): array
    {
        $start = CarbonImmutable::now()->startOfMonth()->subMonths(5);

        $rows = (clone $base)
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '>=', $start)
            ->get(['submitted_at', 'status']);

        $buckets = [];
        for ($i = 0; $i < 6; $i++) {
            $month = $start->addMonths($i);
            $buckets[$month->format('Y-m')] = [
                'label'     => $month->format('M Y'),
                'submitted' => 0,
                'approved'  => 0,
            ];
        }

        foreach ($rows as $row) {
            $key = $row->submitted_at?->format('Y-m');
            if ($key === null || ! isset($buckets[$key])) {
                continue;
            }
            $buckets[$key]['submitted']++;
            if (strtolower((string) $row->status) === 'approved') {
                $buckets[$key]['approved']++;
            }
        }

        return [
            'labels'    => array_column($buckets, 'label'),
            'submitted' => array_column($buckets, 'submitted'),
            'approved'  => array_column($buckets, 'approved'),
        ];
    }

    /**
     * Stream a submissions collection as CSV, shared by both export endpoints.
     */
    private function streamSubmissionsCsv($rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Submission Title', 'Group', 'Stage', 'Status', 'Submitted At']);

            foreach ($rows as $row) {
                $groupOrContext = optional($row->projectGroup)->name;
                if ($groupOrContext === null || $groupOrContext === '') {
                    $student = $row->relationLoaded('student') ? $row->student : null;
                    $groupOrContext = $student?->name
                        ? 'Individual — '.$student->name
                        : '—';
                }
                fputcsv($handle, [
                    $row->title,
                    $groupOrContext,
                    $row->stage,
                    $row->status,
                    optional($row->submitted_at)?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
