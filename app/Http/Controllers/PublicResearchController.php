<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Support\PrmsListFilters;
use App\Models\Document;
use App\Models\ProjectSubmission;
use App\Models\ResearchProject;
use App\Support\PublicPortalPublication;
use App\Support\PrmsTablePagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * FR-11: Public Research Access Portal.
 *
 * Read-only repository for non-authenticated users (public, prospective
 * students, external researchers). Honours embargo, supports search,
 * faceted filters, sorting, citation export (APA/MLA/Chicago/Harvard),
 * and download tracking.
 */
class PublicResearchController extends Controller
{
    /**
     * Citation styles supported on the public portal.
     */
    private const CITATION_STYLES = ['apa', 'mla', 'chicago', 'harvard'];

    /**
     * Sort modes exposed in the UI. Maps to (column, direction) pairs.
     */
    private const SORT_MODES = [
        'recent' => ['published_at', 'desc'],
        'oldest' => ['published_at', 'asc'],
        'title' => ['title', 'asc'],
        'relevance' => ['published_at', 'desc'],
    ];

    /** @var list<string> */
    private const WORK_CATEGORIES = ['proposal', 'research', 'project'];

    public function index(Request $request): View|RedirectResponse
    {
        $defaults = [
            'search' => '',
            'type' => '',
            'department_id' => '',
            'since_year' => '',
            'year_from' => '',
            'year_to' => '',
            'author' => '',
            'sort' => 'recent',
        ];

        if ($request->filled('apply_search')) {
            $current = PrmsListFilters::peek($request, 'public.research.index', $defaults);
            session()->flash(PrmsListFilters::sessionKey('public.research.index'), array_merge($current, [
                'search' => trim((string) $request->query('apply_search')),
            ]));

            return redirect()->route('public.research.index');
        }

        $resolved = PrmsListFilters::resolve(
            $request,
            'public.research.index',
            $defaults,
            'public.research.index',
            [],
            fn (array $filters) => $this->sanitizePublicResearchFilters($filters)
        );

        if ($resolved['redirect'] !== null) {
            return $resolved['redirect'];
        }

        $filters = $resolved['filters'];
        $search = $filters['search'];
        $type = $filters['type'];
        $departmentId = (int) $filters['department_id'];
        $sinceYear = (string) $filters['since_year'];
        $yearFrom = (int) $filters['year_from'];
        $yearTo = (int) $filters['year_to'];
        $author = $filters['author'];
        $sortKey = $filters['sort'];

        [$sortColumn, $sortDir] = self::SORT_MODES[$sortKey];

        $queryStartedAt = microtime(true);

        $publicBaseQuery = ResearchProject::query()
            ->where('is_public', true)
            ->whereNotNull('published_at');

        $categoryCounts = [
            'all' => (clone $publicBaseQuery)->count(),
            'proposal' => (clone $publicBaseQuery)->where('project_type', 'proposal')->count(),
            'research' => (clone $publicBaseQuery)->where('project_type', 'research')->count(),
            'project' => (clone $publicBaseQuery)->where('project_type', 'project')->count(),
        ];

        $query = (clone $publicBaseQuery)->with([
            'projectType',
            'projectGroup.members',
            'student',
            'documents' => fn ($q) => $q
                ->where('is_public', true)
                ->where('is_current_version', true)
                ->latest('upload_date'),
        ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('abstract', 'like', "%{$search}%")
                    ->orWhere('keywords', 'like', "%{$search}%")
                    ->orWhereHas('student', fn ($inner) => $inner->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('projectGroup.members', fn ($inner) => $inner->where('name', 'like', "%{$search}%"))
                    ->orWhereHas(
                        'student.studentProfile.department',
                        fn ($inner) => $inner->where('department_name', 'like', "%{$search}%")
                    )
                    ->orWhereHas(
                        'student.studentProfile.programme.department',
                        fn ($inner) => $inner->where('department_name', 'like', "%{$search}%")
                    );
            });
        }

        if (in_array($type, self::WORK_CATEGORIES, true)) {
            $query->where('project_type', $type);
        }

        if ($sinceYear === 'custom') {
            if ($yearFrom > 0) {
                $query->whereYear('published_at', '>=', $yearFrom);
            }
            if ($yearTo > 0) {
                $query->whereYear('published_at', '<=', $yearTo);
            }
        } elseif (in_array($sinceYear, ['2026', '2025', '2022'], true)) {
            $query->where('published_at', '>=', "{$sinceYear}-01-01 00:00:00");
        }

        if ($author !== '') {
            $query->where(function ($q) use ($author) {
                $q->whereHas('student', fn ($inner) => $inner->where('name', 'like', "%{$author}%"))
                    ->orWhereHas('projectGroup.members', fn ($inner) => $inner->where('name', 'like', "%{$author}%"));
            });
        }

        // Department filtering uses the student's program → department
        // chain because research_projects does not persist a department
        // id on its own row.
        if ($departmentId > 0) {
            $query->whereHas(
                'student.studentProfile.programme',
                fn ($q) => $q->where('department_id', $departmentId)
            );
        }

        $projects = $query->orderBy($sortColumn, $sortDir)
            ->paginate(PrmsTablePagination::perPage($request))
            ->withQueryString();

        $queryElapsed = round(microtime(true) - $queryStartedAt, 2);

        return view('public.research.index', [
            'projects' => $projects,
            'departments' => Department::orderBy('department_name')->get(),
            'authors' => $this->resolvePublicAuthors(),
            'filters' => $filters,
            'categoryCounts' => $categoryCounts,
            'filterResetUrl' => PrmsListFilters::resetUrl('public.research.index'),
            'relatedSearches' => $this->resolveRelatedSearches($search, $type),
            'queryElapsed' => $queryElapsed,
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function sanitizePublicResearchFilters(array $filters): array
    {
        $sortKey = (string) ($filters['sort'] ?? 'recent');

        $type = (string) ($filters['type'] ?? '');

        $sinceYear = (string) ($filters['since_year'] ?? '');

        return [
            'search' => trim((string) ($filters['search'] ?? '')),
            'type' => in_array($type, self::WORK_CATEGORIES, true) ? $type : '',
            'department_id' => max(0, (int) ($filters['department_id'] ?? 0)),
            'since_year' => in_array($sinceYear, ['', '2026', '2025', '2022', 'custom'], true) ? $sinceYear : '',
            'year_from' => max(0, (int) ($filters['year_from'] ?? 0)),
            'year_to' => max(0, (int) ($filters['year_to'] ?? 0)),
            'author' => trim((string) ($filters['author'] ?? '')),
            'sort' => array_key_exists($sortKey, self::SORT_MODES) ? $sortKey : 'recent',
        ];
    }

    /**
     * @return Collection<int, string>
     */
    private function resolvePublicAuthors(): Collection
    {
        $names = collect();

        ResearchProject::query()
            ->where('is_public', true)
            ->whereNotNull('published_at')
            ->with(['student', 'projectGroup.members'])
            ->orderByDesc('published_at')
            ->chunk(100, function ($projects) use ($names): void {
                foreach ($projects as $project) {
                    if ($project->student?->name) {
                        $names->push($project->student->name);
                    }

                    foreach ($project->projectGroup?->members ?? [] as $member) {
                        if ($member->name) {
                            $names->push($member->name);
                        }
                    }
                }
            });

        return $names
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * @return list<string>
     */
    private function resolveRelatedSearches(string $search, string $type): array
    {
        $terms = collect();

        if ($search !== '') {
            $terms->push($search.' management system');
            $terms->push($search.' system');
            if (! str_contains(strtolower($search), 'management')) {
                $terms->push($search.' management');
            }
        }

        $keywordQuery = ResearchProject::query()
            ->where('is_public', true)
            ->whereNotNull('published_at');

        if (in_array($type, self::WORK_CATEGORIES, true)) {
            $keywordQuery->where('project_type', $type);
        } elseif ($type === '') {
            $keywordQuery->whereIn('project_type', ['proposal', 'research']);
        }

        $keywordQuery
            ->orderByDesc('published_at')
            ->limit(40)
            ->get(['keywords', 'title', 'research_area', 'abstract'])
            ->each(function (ResearchProject $project) use ($terms): void {
                if ($project->keywords) {
                    foreach (explode(',', (string) $project->keywords) as $keyword) {
                        $terms->push(trim($keyword));
                    }
                }

                if ($project->research_area) {
                    $terms->push(trim((string) $project->research_area));
                }

                if ($project->title) {
                    $terms->push(trim((string) $project->title));
                }
            });

        $fallbacks = [
            'research proposal',
            'literature review',
            'database management system',
            'inventory management system',
            'record management system',
            'document management system',
            'attendance management system',
        ];

        return $terms
            ->merge($fallbacks)
            ->map(fn ($term) => Str::lower(trim((string) $term)))
            ->filter(fn ($term) => strlen($term) > 3)
            ->unique()
            ->take(8)
            ->values()
            ->all();
    }

    public function show(ResearchProject $project): View
    {
        if (!$project->is_public) {
            abort(404);
        }

        $project->load(['projectType', 'projectGroup.members', 'student']);

        $document = $this->resolvePublicDocument($project);

        return view('public.research.show', [
            'project' => $project,
            'document' => $document,
            'showcaseSubmission' => PublicPortalPublication::resolveShowcaseSubmission($project),
            'isUnderEmbargo' => $this->isUnderEmbargo($document),
            'citations' => $this->buildCitations($project),
        ]);
    }

    public function screenshot(Request $request, ResearchProject $project): Response
    {
        $this->ensurePublicProject($project);

        $showcase = PublicPortalPublication::resolveShowcaseSubmission($project);
        if ($showcase === null) {
            abort(404, 'Screenshot not found.');
        }

        $showcase->loadMissing('interfaceScreenshots');
        if ($showcase->interfaceScreenshots->isEmpty() && ! $showcase->screenshot_path) {
            abort(404, 'Screenshot not found.');
        }

        $index = max(0, (int) $request->query('interface', 0));
        $shot = $showcase->interfaceScreenshots->get($index)
            ?? $showcase->primaryInterfaceScreenshot();
        $path = $shot?->file_path ?: $showcase->screenshot_path;
        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404, 'Screenshot not found.');
        }

        $filename = $shot?->original_filename
            ?: $showcase->screenshot_original_filename
            ?: basename($path);
        $mime = $shot?->mime_type
            ?: $showcase->screenshot_mime_type
            ?: (Storage::disk('public')->mimeType($path) ?: 'image/png');

        return Storage::disk('public')->response($path, $filename, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function showcase(ResearchProject $project): View
    {
        $this->ensurePublicProject($project);

        $showcase = PublicPortalPublication::resolveShowcaseSubmission($project);
        if ($showcase === null || ! $showcase->isProjectShowcase()) {
            abort(404, 'This project does not have a public showcase.');
        }

        $project->load(['student', 'projectGroup.members']);

        return view('public.research.showcase', [
            'project' => $project,
            'submission' => $showcase,
            'stageLabel' => \Illuminate\Support\Str::title(str_replace('_', ' ', (string) $showcase->stage)),
            'openDemo' => request()->query('open') === 'demo',
        ]);
    }

    public function sourceDownload(ResearchProject $project): Response
    {
        $this->ensurePublicProject($project);

        $showcase = PublicPortalPublication::resolveShowcaseSubmission($project);
        if ($showcase === null || ! $showcase->file_path) {
            abort(404, 'Source archive not found.');
        }

        if (! Storage::disk('public')->exists($showcase->file_path)) {
            abort(404, 'Source archive not found.');
        }

        $filename = $showcase->original_filename ?: basename($showcase->file_path);

        return Storage::disk('public')->download($showcase->file_path, $filename);
    }

    private function ensurePublicProject(ResearchProject $project): void
    {
        if (! $project->is_public || $project->published_at === null) {
            abort(404);
        }
    }

    /**
     * Stream a downloadable copy of the public document, if one exists,
     * is published, and is not currently under embargo.
     */
    public function download(Request $request, ResearchProject $project): Response
    {
        if (!$project->is_public) {
            abort(404);
        }

        $document = $this->resolvePublicDocument($project);

        if (!$document) {
            abort(404, 'No public document is attached to this research record.');
        }

        if ($this->isUnderEmbargo($document)) {
            abort(403, 'This document is currently under embargo.');
        }

        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File missing from storage.');
        }

        $document->increment('download_count');

        $project->increment('citation_count');

        $filename = $document->file_name ?: ($project->title.'.pdf');

        return Storage::disk('public')->download($document->file_path, $filename);
    }

    /**
     * Return a copy-friendly text citation for the requested style.
     * Always returns 200 with a plaintext body so users can copy/paste.
     */
    public function citation(Request $request, ResearchProject $project, string $style): Response
    {
        if (!$project->is_public) {
            abort(404);
        }

        $style = strtolower($style);

        if (!in_array($style, self::CITATION_STYLES, true)) {
            abort(404);
        }

        $project->load(['student', 'projectGroup.members', 'projectType']);
        $citations = $this->buildCitations($project);

        return response($citations[$style] ?? '', 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    /**
     * Build all four supported citation strings for a project.
     *
     * @return array<string, string>
     */
    private function buildCitations(ResearchProject $project): array
    {
        $year = $project->published_at ? $project->published_at->format('Y') : now()->format('Y');
        $title = trim((string) $project->title);
        $authors = $this->resolveAuthorList($project);
        $institution = 'Moshi Co-operative University Institutional Repository';

        // APA: Authors. (Year). Title. Institution.
        $apaAuthors = $this->joinAuthorsApa($authors);
        $apa = sprintf('%s (%s). %s. %s.', $apaAuthors, $year, $title, $institution);

        // MLA: First Author, Co-author. "Title." Institution, Year.
        $mlaAuthors = $this->joinAuthorsMla($authors);
        $mla = sprintf('%s "%s." %s, %s.', $mlaAuthors, $title, $institution, $year);

        // Chicago (author-date): Author 1, Author 2. Year. "Title." Institution.
        $chicagoAuthors = $this->joinAuthorsChicago($authors);
        $chicago = sprintf('%s %s. "%s." %s.', $chicagoAuthors, $year, $title, $institution);

        // Harvard: Authors (Year) Title. Institution.
        $harvardAuthors = $this->joinAuthorsHarvard($authors);
        $harvard = sprintf('%s (%s) %s. %s.', $harvardAuthors, $year, $title, $institution);

        return [
            'apa' => $apa,
            'mla' => $mla,
            'chicago' => $chicago,
            'harvard' => $harvard,
        ];
    }

    /**
     * Resolve a list of author names for the project (lead student plus
     * any co-members in the group). Falls back to a placeholder if none
     * are configured so citations remain usable.
     *
     * @return array<int, string>
     */
    private function resolveAuthorList(ResearchProject $project): array
    {
        $names = [];

        if ($project->student) {
            $names[] = $project->student->name;
        }

        if ($project->projectGroup) {
            foreach ($project->projectGroup->members as $member) {
                if (!in_array($member->name, $names, true)) {
                    $names[] = $member->name;
                }
            }
        }

        if (empty($names)) {
            $names[] = 'Anonymous';
        }

        return array_values(array_filter($names, fn ($n) => trim((string) $n) !== ''));
    }

    private function joinAuthorsApa(array $names): string
    {
        $formatted = array_map(fn ($n) => $this->formatAuthorLastFirstInitial($n), $names);

        if (count($formatted) === 1) {
            return $formatted[0];
        }

        $last = array_pop($formatted);
        return implode(', ', $formatted).', & '.$last;
    }

    private function joinAuthorsMla(array $names): string
    {
        if (count($names) === 1) {
            return $this->formatAuthorLastFirst($names[0]).'.';
        }

        return $this->formatAuthorLastFirst($names[0]).', et al.';
    }

    private function joinAuthorsChicago(array $names): string
    {
        $formatted = array_map(fn ($n) => $this->formatAuthorLastFirst($n), $names);

        if (count($formatted) === 1) {
            return $formatted[0].'.';
        }

        $last = array_pop($formatted);
        return implode(', ', $formatted).', and '.$last.'.';
    }

    private function joinAuthorsHarvard(array $names): string
    {
        $formatted = array_map(fn ($n) => $this->formatAuthorLastFirstInitial($n), $names);

        if (count($formatted) === 1) {
            return $formatted[0];
        }

        $last = array_pop($formatted);
        return implode(', ', $formatted).' and '.$last;
    }

    private function formatAuthorLastFirstInitial(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        if (count($parts) < 2) {
            return $name;
        }

        $surname = array_pop($parts);
        $initials = collect($parts)
            ->map(fn ($p) => Str::upper(Str::substr($p, 0, 1)).'.')
            ->implode(' ');

        return $surname.', '.$initials;
    }

    private function formatAuthorLastFirst(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        if (count($parts) < 2) {
            return $name;
        }

        $surname = array_pop($parts);
        return $surname.', '.implode(' ', $parts);
    }

    /**
     * The current "downloadable" document for the project: either the
     * latest project_submission tied to the group/student, or the
     * latest Document attached directly. Returns null when nothing is
     * available so the view can degrade gracefully.
     */
    private function resolvePublicDocument(ResearchProject $project): ?Document
    {
        return Document::query()
            ->where('project_id', $project->id)
            ->where('is_public', true)
            ->where('is_current_version', true)
            ->latest('upload_date')
            ->first();
    }

    private function isUnderEmbargo(?Document $document): bool
    {
        if (!$document || !$document->embargo_until) {
            return false;
        }

        return now()->lessThan($document->embargo_until);
    }
}
