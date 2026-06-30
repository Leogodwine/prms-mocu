<?php

namespace App\Http\Controllers;

use App\Models\ProjectStage;
use App\Models\ProjectSubmission;
use App\Models\User;
use App\Support\Audit;
use App\Support\PrmsListFilters;
use App\Support\PrmsTablePagination;
use App\Support\RepositoryPublication;
use App\Support\StudentStageProgress;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ArchiveController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        $defaults = [
            'stage' => '',
            'q' => '',
            'type' => '',
            'status' => 'approved',
        ];

        $lockedType = $this->lockedArchiveType($request);
        if ($lockedType !== null) {
            $defaults['type'] = $lockedType;
        }

        $routeParams = $lockedType !== null ? ['type' => $lockedType] : [];

        if ($request->filled('apply_q')) {
            $current = PrmsListFilters::peek($request, 'archive.index', $defaults);
            session()->flash(PrmsListFilters::sessionKey('archive.index'), array_merge($current, [
                'q' => trim((string) $request->query('apply_q')),
            ]));

            return redirect()->route('archive.index', $routeParams);
        }

        $resolved = PrmsListFilters::resolve(
            $request,
            'archive.index',
            $defaults,
            'archive.index',
            $routeParams,
            fn (array $filters) => $this->sanitizeArchiveFilters($filters, $lockedType)
        );

        if ($resolved['redirect'] !== null) {
            return $resolved['redirect'];
        }

        $filters = $resolved['filters'];
        if ($lockedType !== null) {
            $filters['type'] = $lockedType;
        }

        $stage = $filters['stage'];
        $search = $filters['q'];
        $workType = $filters['type'];
        $statusFilter = $filters['status'];

        $baseQuery = $this->scopedArchiveQuery($user);
        $query = $this->applyArchiveFilters(clone $baseQuery, $user, $stage, $search, $workType, $statusFilter);

        $submissions = (clone $query)->latest()->paginate(
            PrmsTablePagination::perPage($request)
        )->withQueryString();

        $stages = $this->stageOptions($workType);
        $progress = $this->buildProgressSummary($user, $baseQuery);
        $canTrackProgress = in_array($user->role, ['supervisor', 'coordinator', 'hod'], true);
        $workTypes = $lockedType !== null ? [$lockedType] : ['proposal', 'research', 'project'];

        return view('archive.index', [
            'submissions' => $submissions,
            'filters' => $filters,
            'stages' => $stages,
            'workTypes' => $workTypes,
            'lockedWorkType' => $lockedType,
            'progress' => $progress,
            'canTrackProgress' => $canTrackProgress,
            'filterResetUrl' => PrmsListFilters::resetUrl('archive.index', $routeParams),
        ]);
    }

    /**
     * @return 'proposal'|'research'|'project'|null
     */
    private function lockedArchiveType(Request $request): ?string
    {
        $type = (string) $request->query('type', '');

        return in_array($type, ['proposal', 'research', 'project'], true) ? $type : null;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function sanitizeArchiveFilters(array $filters, ?string $lockedType = null): array
    {
        $workType = $filters['type'] ?? '';
        $statusFilter = $filters['status'] ?? 'approved';

        if ($lockedType !== null) {
            $workType = $lockedType;
        }

        return [
            'stage' => (string) ($filters['stage'] ?? ''),
            'q' => trim((string) ($filters['q'] ?? '')),
            'type' => in_array($workType, ['', 'proposal', 'research', 'project'], true) ? $workType : '',
            'status' => in_array($statusFilter, ['approved', 'in_progress', 'all'], true) ? $statusFilter : 'approved',
        ];
    }

    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        $filters = PrmsListFilters::peek($request, 'archive.index', [
            'stage' => '',
            'q' => '',
            'type' => '',
            'status' => 'approved',
        ]);
        $filters = $this->sanitizeArchiveFilters($filters);
        $stage = $filters['stage'];
        $search = $filters['q'];
        $workType = $filters['type'];
        $statusFilter = $filters['status'];

        $rows = $this->applyArchiveFilters(
            $this->scopedArchiveQuery($user),
            $user,
            $stage,
            $search,
            $workType,
            $statusFilter
        )->latest()->get();

        Audit::log($request, 'archive.export', 'Archive', null, null, [
            'row_count' => $rows->count(),
            'filters' => $filters,
        ]);

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Title', 'Work type', 'Owner', 'Stage', 'Status', 'Submitted at']);

            foreach ($rows as $row) {
                $owner = optional($row->student)->name ?: optional($row->projectGroup)->name;
                $type = StudentStageProgress::workTypeFromStage((string) $row->stage);

                fputcsv($handle, [
                    $row->title,
                    StudentStageProgress::workTypeLabel($type),
                    $owner,
                    $row->stage,
                    $row->status,
                    optional($row->submitted_at)?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, 'approved-proposals-research-projects.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function scopedArchiveQuery(User $user): Builder
    {
        $query = ProjectSubmission::query()
            ->with(['projectGroup.members', 'student', 'projectGroup.supervisorAssignment', 'feedback.supervisor']);

        if ($user->role === 'supervisor') {
            $supervisorId = $user->id;
            $query->where(function ($q) use ($supervisorId) {
                $q->whereHas('projectGroup.supervisorAssignment', fn ($inner) => $inner->where('supervisor_id', $supervisorId))
                    ->orWhereHas('student.studentAssignment', fn ($inner) => $inner->where('supervisor_id', $supervisorId));
            });
        } elseif ($user->role === 'coordinator') {
            $query->where(function ($q) use ($user) {
                $q->whereHas('projectGroup', fn ($inner) => $inner->where('coordinator_id', $user->id))
                    ->orWhereHas('student.projectGroups', fn ($inner) => $inner->where('coordinator_id', $user->id));
            });
        }

        return $query;
    }

    private function applyArchiveFilters(
        Builder $query,
        User $user,
        string $stage,
        string $search,
        string $workType,
        string $statusFilter,
    ): Builder {
        if ($statusFilter === 'approved') {
            $query->whereRaw('LOWER(COALESCE(status, "")) = ?', ['approved']);
        } elseif ($statusFilter === 'in_progress') {
            $query->whereRaw('LOWER(COALESCE(status, "")) IN (?,?,?,?)', [
                'pending', 'submitted', 'under_review', 'needs_revision',
            ]);
        }

        if ($workType !== '') {
            StudentStageProgress::scopeWorkType($query, $workType);
        }

        if (in_array($user->role, ['project_student', 'research_student', 'normal_student', 'student'], true)) {
            $query->whereNotNull('repository_published_at')
                ->where(function (Builder $inner) {
                    foreach (StudentStageProgress::completeDocumentStageNames() as $stageName) {
                        $inner->orWhere('stage', $stageName);
                    }
                });
        }

        if ($stage !== '') {
            $query->where('stage', $stage);
        }

        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('stage', 'like', "%{$search}%")
                    ->orWhereHas('projectGroup', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('student', fn ($q) => $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    /**
     * @return list<string>
     */
    private function stageOptions(string $workType): array
    {
        if (! Schema::hasTable('project_stages')) {
            return [];
        }

        $stages = ProjectStage::query()->orderBy('stage_order')->get();

        if ($workType !== '') {
            $stages = StudentStageProgress::stagesForTrack($stages, $workType);
        }

        return $stages->pluck('stage_name')->filter()->values()->all();
    }

    /**
     * @return array<string, array{approved: int, in_progress: int, total: int, label: string}>
     */
    private function buildProgressSummary(User $user, Builder $baseQuery): array
    {
        if (! in_array($user->role, ['supervisor', 'coordinator', 'hod', 'admin'], true)) {
            return [];
        }

        $rows = (clone $baseQuery)->get(['id', 'stage', 'status']);
        $summary = [];

        foreach (['proposal', 'research', 'project'] as $type) {
            $summary[$type] = [
                'label' => StudentStageProgress::workTypeLabel($type),
                'approved' => 0,
                'in_progress' => 0,
                'total' => 0,
            ];
        }

        foreach ($rows as $row) {
            $type = StudentStageProgress::workTypeFromStage((string) $row->stage);
            if ($type === 'other' || ! isset($summary[$type])) {
                continue;
            }

            $st = strtolower((string) ($row->status ?? ''));
            if ($st === 'approved') {
                $summary[$type]['approved']++;
                $summary[$type]['total']++;
            } elseif (in_array($st, ['pending', 'submitted', 'under_review', 'needs_revision'], true)) {
                $summary[$type]['in_progress']++;
                $summary[$type]['total']++;
            }
        }

        return $summary;
    }
}
