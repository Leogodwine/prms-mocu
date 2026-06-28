<?php

namespace App\Support;

use App\Models\ProjectStage;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Single source of truth for PRMS sidebar links and quick navigation.
 */
final class PrmsNavigationIndex
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function forUser(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        $role = (string) $user->role;
        $items = array_merge(
            self::dashboardItem(),
            self::studentItems($role),
            self::coordinatorItems($role),
            self::supervisorItems($role),
            self::hodItems($role),
            self::adminItems($role),
            self::libraryItems($role),
            self::notificationsItem(),
            self::accountItems(),
        );

        return array_values(array_filter(
            $items,
            fn (array $item) => self::roleAllowed($role, $item['roles'] ?? null)
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function sidebarForUser(?User $user): array
    {
        $items = array_values(array_filter(
            self::forUser($user),
            fn (array $item) => ! empty($item['sidebar'])
        ));

        if ($user !== null && in_array((string) $user->role, ['project_student', 'research_student', 'normal_student'], true)) {
            $items = self::attachStudentWorkspaceChildren($user, $items);
        }

        usort($items, function (array $a, array $b): int {
            $orderA = $a['sidebar_order'] ?? PHP_INT_MAX;
            $orderB = $b['sidebar_order'] ?? PHP_INT_MAX;

            return $orderA <=> $orderB;
        });

        return $items;
    }

    /**
     * Flat quick-find destinations (pages + workspace chapters for students).
     *
     * @return list<array{label: string, url: string, icon: string, group: string, keywords: string, subtitle: string}>
     */
    public static function quickNavForUser(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        $destinations = [];
        $seenUrls = [];

        foreach (self::forUser($user) as $item) {
            $url = (string) ($item['url'] ?? '');
            if ($url === '') {
                continue;
            }
            $destinations[] = self::quickNavEntry($item);
            $seenUrls[$url] = true;
        }

        if (in_array((string) $user->role, ['project_student', 'research_student', 'normal_student'], true)) {
            foreach (self::sidebarForUser($user) as $parent) {
                foreach ($parent['children'] ?? [] as $child) {
                    $url = (string) ($child['url'] ?? '');
                    if ($url === '' || isset($seenUrls[$url])) {
                        continue;
                    }
                    $seenUrls[$url] = true;
                    $destinations[] = [
                        'label' => (string) ($child['label'] ?? ''),
                        'url' => $url,
                        'icon' => (string) ($parent['icon'] ?? 'fas fa-file'),
                        'group' => (string) ($parent['group'] ?? 'Student workspace'),
                        'keywords' => trim(
                            (string) ($parent['keywords'] ?? '').' '
                            .(string) ($parent['label'] ?? '').' '
                            .(string) ($child['label'] ?? '')
                        ),
                        'subtitle' => (string) ($parent['label'] ?? 'Student workspace'),
                    ];
                }
            }
        }

        return $destinations;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{label: string, url: string, icon: string, group: string, keywords: string, subtitle: string}
     */
    private static function quickNavEntry(array $item): array
    {
        return [
            'label' => (string) ($item['label'] ?? ''),
            'url' => (string) ($item['url'] ?? ''),
            'icon' => (string) ($item['icon'] ?? 'fas fa-link'),
            'group' => (string) ($item['group'] ?? 'Pages'),
            'keywords' => (string) ($item['keywords'] ?? ''),
            'subtitle' => (string) ($item['group'] ?? 'Pages'),
        ];
    }

    public static function isWorkspaceParentActive(array $item, ?Request $request = null): bool
    {
        if (self::isActive($item, $request)) {
            return true;
        }

        foreach ($item['children'] ?? [] as $child) {
            if (self::isActive($child, $request)) {
                return true;
            }
        }

        return false;
    }

    public static function isWorkspaceOverviewActive(array $item, ?Request $request = null): bool
    {
        $request ??= request();

        if (! self::isActive($item, $request)) {
            return false;
        }

        return ! $request->filled('stage_id');
    }

    public static function isActive(array $item, ?Request $request = null): bool
    {
        $request ??= request();

        if (isset($item['active']) && is_callable($item['active'])) {
            return (bool) $item['active']($request);
        }

        if (empty($item['route_is'])) {
            return false;
        }

        if (! $request->routeIs($item['route_is'])) {
            return false;
        }

        foreach ($item['route_query'] ?? [] as $key => $value) {
            if ((string) $request->query($key) !== (string) $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>|null  $roles
     */
    private static function roleAllowed(string $role, ?array $roles): bool
    {
        if ($roles === null) {
            return true;
        }

        return in_array($role, $roles, true);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function dashboardItem(): array
    {
        return [
            [
                'label' => 'Dashboard',
                'url' => route('dashboard'),
                'icon' => 'fas fa-th-large',
                'group' => 'Main',
                'keywords' => 'home start overview',
                'sidebar' => true,
                'sidebar_order' => 10,
                'route_is' => 'dashboard',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function notificationsItem(): array
    {
        return [
            [
                'label' => 'Notifications',
                'url' => route('notifications.index'),
                'icon' => 'far fa-bell',
                'group' => 'Main',
                'keywords' => 'alerts messages inbox',
                'sidebar' => true,
                'sidebar_order' => 1000,
                'route_is' => 'notifications.*',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function studentItems(string $role): array
    {
        if (! in_array($role, ['project_student', 'research_student', 'normal_student'], true)) {
            return [];
        }

        $items = [
            [
                'label' => 'New project/proposal creation',
                'url' => route('projects.index'),
                'icon' => 'fas fa-plus-circle',
                'group' => 'Student workspace',
                'keywords' => 'new create project proposal problem statement title',
                'roles' => ['project_student', 'research_student', 'normal_student'],
                'sidebar' => true,
                'sidebar_order' => 15,
                'route_is' => 'projects.*',
            ],
            [
                'label' => 'Research proposal',
                'url' => route('student.index', ['type' => 'proposal']),
                'icon' => 'far fa-file-alt',
                'group' => 'Student workspace',
                'keywords' => 'proposal chapter upload submit',
                'roles' => ['project_student', 'research_student', 'normal_student'],
                'sidebar' => true,
                'sidebar_order' => 20,
                'route_is' => 'student.index',
                'route_query' => ['type' => 'proposal'],
                'workspace_track' => 'proposal',
            ],
            [
                'label' => 'Research report',
                'url' => route('student.index', ['type' => 'research']),
                'icon' => 'fas fa-book-open',
                'group' => 'Student workspace',
                'keywords' => 'research thesis dissertation chapter',
                'roles' => ['project_student', 'research_student', 'normal_student'],
                'sidebar' => true,
                'sidebar_order' => 30,
                'route_is' => 'student.index',
                'route_query' => ['type' => 'research'],
                'workspace_track' => 'research',
            ],
        ];

        if (in_array($role, ['project_student', 'normal_student'], true)) {
            $items[] = [
                'label' => 'Project workspace',
                'url' => route('student.index', ['type' => 'project']),
                'icon' => 'fas fa-laptop-code',
                'group' => 'Student workspace',
                'keywords' => 'source code showcase demo presentation consent',
                'roles' => ['project_student', 'normal_student'],
                'sidebar' => true,
                'sidebar_order' => 40,
                'route_is' => 'student.index',
                'route_query' => ['type' => 'project'],
                'workspace_track' => 'project',
            ];
        }

        return $items;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private static function attachStudentWorkspaceChildren(User $user, array $items): array
    {
        $projectGroup = $user->projectGroups()->first();
        $stages = ProjectStage::query()->orderBy('stage_order')->get();
        $latestByStage = StudentStageProgress::latestSubmissionByStage($user, $projectGroup);

        return array_map(function (array $item) use ($stages, $latestByStage) {
            $track = $item['workspace_track'] ?? null;

            if (! is_string($track) || $track === '') {
                return $item;
            }

            $item['collapse_id'] = 'nav-ws-'.$track;
            $item['children'] = StudentStageProgress::stagesForTrack($stages, $track)
                ->map(function (ProjectStage $stage) use ($track, $latestByStage) {
                    $navMeta = StudentStageProgress::navStatusMeta($latestByStage->get($stage->stage_name));

                    return [
                        'label' => StudentStageProgress::shortStageLabel($stage->stage_name),
                        'url' => route('student.index', ['type' => $track, 'stage_id' => $stage->id]),
                        'route_is' => 'student.index',
                        'route_query' => [
                            'type' => $track,
                            'stage_id' => (string) $stage->id,
                        ],
                        'status' => $navMeta,
                    ];
                })
                ->values()
                ->all();

            return $item;
        }, $items);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function coordinatorItems(string $role): array
    {
        if ($role !== 'coordinator') {
            return [];
        }

        return [
            [
                'label' => 'Groups & assignments',
                'url' => route('coordinator.index'),
                'icon' => 'fas fa-users-cog',
                'group' => 'Coordinator',
                'keywords' => 'groups formation auto-group supervisor assign',
                'roles' => ['coordinator'],
                'sidebar' => true,
                'sidebar_order' => 20,
                'route_is' => 'coordinator.index',
            ],
            [
                'label' => 'Deadlines',
                'url' => route('coordinator.deadlines'),
                'icon' => 'far fa-clock',
                'group' => 'Coordinator',
                'keywords' => 'timeline stage window academic year',
                'roles' => ['coordinator'],
                'sidebar' => true,
                'sidebar_order' => 30,
                'route_is' => 'coordinator.deadlines',
            ],
            [
                'label' => 'Final submissions',
                'url' => route('coordinator.submissions'),
                'icon' => 'fas fa-file-signature',
                'group' => 'Coordinator',
                'keywords' => 'approve finalize archive download',
                'roles' => ['coordinator'],
                'sidebar' => true,
                'sidebar_order' => 40,
                'route_is' => 'coordinator.submissions',
            ],
            [
                'label' => 'Grading schemes',
                'url' => route('coordinator.rubrics.index'),
                'icon' => 'fas fa-clipboard-check',
                'group' => 'Coordinator',
                'keywords' => 'rubrics criteria marks evaluation',
                'roles' => ['coordinator'],
                'sidebar' => false,
                'route_is' => 'coordinator.rubrics.*',
            ],
            [
                'label' => 'Reports & analytics',
                'url' => route('reports.coordinator'),
                'icon' => 'fas fa-chart-pie',
                'group' => 'Coordinator',
                'keywords' => 'statistics progress workload export',
                'roles' => ['coordinator'],
                'sidebar' => true,
                'sidebar_order' => 95,
                'route_is' => 'reports.coordinator',
            ],
            [
                'label' => 'Submission materials report',
                'url' => route('reports.coordinator.materials'),
                'icon' => 'fas fa-folder-open',
                'group' => 'Coordinator',
                'keywords' => 'materials files documents list',
                'roles' => ['coordinator'],
                'sidebar' => false,
                'route_is' => 'reports.coordinator.materials',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function supervisorItems(string $role): array
    {
        if ($role !== 'supervisor') {
            return [];
        }

        return [
            [
                'label' => 'Assigned students',
                'url' => route('supervisor.workload'),
                'icon' => 'fas fa-user-graduate',
                'group' => 'Supervisor',
                'keywords' => 'assigned students groups individuals workload roster',
                'roles' => ['supervisor'],
                'sidebar' => true,
                'sidebar_order' => 20,
                'route_is' => 'supervisor.workload',
            ],
            [
                'label' => 'Supervision Management',
                'url' => route('supervisor.logs'),
                'icon' => 'fas fa-clipboard-list',
                'group' => 'Supervisor',
                'keywords' => 'supervision management meeting notes progress agreed actions history',
                'roles' => ['supervisor'],
                'sidebar' => true,
                'sidebar_order' => 30,
                'route_is' => 'supervisor.logs*',
            ],
            [
                'label' => 'Review submissions',
                'url' => route('supervisor.index'),
                'icon' => 'fas fa-clipboard-check',
                'group' => 'Supervisor',
                'keywords' => 'review pending approve reject evaluate',
                'roles' => ['supervisor'],
                'sidebar' => true,
                'sidebar_order' => 40,
                'route_is' => 'supervisor.index',
            ],
            [
                'label' => 'Reports & analytics',
                'url' => route('reports.supervisor'),
                'icon' => 'fas fa-chart-pie',
                'group' => 'Supervisor',
                'keywords' => 'statistics export progress',
                'roles' => ['supervisor'],
                'sidebar' => true,
                'sidebar_order' => 95,
                'route_is' => 'reports.supervisor',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function hodItems(string $role): array
    {
        if ($role !== 'hod') {
            return [];
        }

        return [
            [
                'label' => 'Department overview',
                'url' => route('hod.index'),
                'icon' => 'fas fa-chart-line',
                'group' => 'HOD',
                'keywords' => 'department statistics summary',
                'roles' => ['hod'],
                'sidebar' => true,
                'sidebar_order' => 20,
                'route_is' => 'hod.index',
            ],
            [
                'label' => 'Student records',
                'url' => route('hod.students.index'),
                'icon' => 'fas fa-user-graduate',
                'group' => 'HOD',
                'keywords' => 'students enrolment programme year',
                'roles' => ['hod'],
                'sidebar' => true,
                'sidebar_order' => 30,
                'route_is' => 'hod.students.*',
            ],
            [
                'label' => 'Reports & analytics',
                'url' => route('reports.coordinator'),
                'icon' => 'fas fa-chart-pie',
                'group' => 'HOD',
                'keywords' => 'statistics export department',
                'roles' => ['hod'],
                'sidebar' => true,
                'sidebar_order' => 95,
                'route_is' => 'reports.*',
            ],
            [
                'label' => 'Academic configuration',
                'url' => route('admin.academic-configuration.index'),
                'icon' => 'fas fa-graduation-cap',
                'group' => 'HOD',
                'keywords' => 'academic eligibility workflow programme department final year research project',
                'roles' => ['hod'],
                'sidebar' => true,
                'sidebar_order' => 40,
                'route_is' => 'admin.academic-configuration.*',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function adminItems(string $role): array
    {
        if ($role !== 'admin') {
            return [];
        }

        return [
            [
                'label' => 'User management',
                'url' => route('admin.users.index'),
                'icon' => 'fas fa-users',
                'group' => 'Administration',
                'keywords' => 'users accounts roles import',
                'roles' => ['admin'],
                'sidebar' => true,
                'route_is' => 'admin.users.*',
            ],
            [
                'label' => 'System monitoring',
                'url' => route('admin.system-health'),
                'icon' => 'fas fa-heartbeat',
                'group' => 'Administration',
                'keywords' => 'monitoring maintenance logs performance memory disk',
                'roles' => ['admin'],
                'sidebar' => true,
                'route_is' => 'admin.system-health*',
            ],
            [
                'label' => 'Backup & recovery',
                'url' => route('admin.backups.index'),
                'icon' => 'fas fa-database',
                'group' => 'Administration',
                'keywords' => 'backup restore database recovery schedule',
                'roles' => ['admin'],
                'sidebar' => true,
                'route_is' => 'admin.backups.*',
            ],
            [
                'label' => 'Academic configuration',
                'url' => route('admin.academic-configuration.index'),
                'icon' => 'fas fa-graduation-cap',
                'group' => 'Administration',
                'keywords' => 'academic eligibility workflow programme department final year research project',
                'roles' => ['admin', 'hod'],
                'sidebar' => true,
                'route_is' => 'admin.academic-configuration.*',
            ],
            [
                'label' => 'System settings',
                'url' => route('admin.configuration.index'),
                'icon' => 'fas fa-cog',
                'group' => 'Administration',
                'keywords' => 'configuration deadlines academic year lifecycle',
                'roles' => ['admin'],
                'sidebar' => true,
                'route_is' => 'admin.configuration.*',
            ],
            [
                'label' => 'SIS synchronization',
                'url' => route('admin.sis-sync'),
                'icon' => 'fas fa-sync-alt',
                'group' => 'Administration',
                'keywords' => 'sis students import gender sync',
                'roles' => ['admin'],
                'sidebar' => false,
                'route_is' => 'admin.sis-sync',
            ],
            [
                'label' => 'Similarity reports',
                'url' => route('admin.similarities.index'),
                'icon' => 'fas fa-clone',
                'group' => 'Administration',
                'keywords' => 'plagiarism duplicate projects',
                'roles' => ['admin'],
                'sidebar' => true,
                'route_is' => 'admin.similarities.*',
            ],
            [
                'label' => 'Audit log',
                'url' => route('admin.audit'),
                'icon' => 'fas fa-shield-alt',
                'group' => 'Administration',
                'keywords' => 'security history login activity',
                'roles' => ['admin'],
                'sidebar' => true,
                'route_is' => 'admin.audit',
            ],
            [
                'label' => 'Reports & analytics',
                'url' => route('reports.coordinator'),
                'icon' => 'fas fa-chart-pie',
                'group' => 'Administration',
                'keywords' => 'statistics coordinator export',
                'roles' => ['admin'],
                'sidebar' => false,
                'route_is' => 'reports.coordinator',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function libraryItems(string $role): array
    {
        $items = [];

        if (in_array($role, ['project_student', 'research_student', 'normal_student'], true)) {
            $items[] = [
                'label' => 'Public repository',
                'url' => route('public.research.index'),
                'icon' => 'fas fa-globe',
                'group' => 'Library',
                'keywords' => 'public research browse search citation',
                'roles' => ['project_student', 'research_student', 'normal_student'],
                'sidebar' => true,
                'sidebar_order' => 90,
                'route_is' => 'public.research.*',
            ];
        }

        if (in_array($role, ['coordinator', 'supervisor', 'hod'], true)) {
            foreach ([
                'proposal' => ['Students proposals', 'far fa-file-alt', 60],
                'research' => ['Students reports', 'fas fa-book-open', 70],
                'project' => ['Students projects', 'fas fa-laptop-code', 80],
            ] as $type => [$label, $icon, $order]) {
                $items[] = [
                    'label' => $label,
                    'url' => route('archive.index', ['type' => $type]),
                    'icon' => $icon,
                    'group' => 'Library',
                    'keywords' => "students {$type} archive documents download",
                    'roles' => ['coordinator', 'supervisor', 'hod'],
                    'sidebar' => true,
                    'sidebar_order' => $order,
                    'route_is' => 'archive.*',
                    'route_query' => ['type' => $type],
                ];
            }

            $items[] = [
                'label' => 'Public repository',
                'url' => route('public.research.index'),
                'icon' => 'fas fa-globe',
                'group' => 'Library',
                'keywords' => 'public research browse search',
                'roles' => ['coordinator', 'supervisor', 'hod'],
                'sidebar' => true,
                'sidebar_order' => 90,
                'route_is' => 'public.research.*',
            ];

            $items[] = [
                'label' => 'Export approved library',
                'url' => route('archive.export'),
                'icon' => 'fas fa-file-csv',
                'group' => 'Library',
                'keywords' => 'csv export download archive',
                'roles' => ['coordinator', 'supervisor', 'hod'],
                'sidebar' => false,
                'route_is' => 'archive.export',
            ];
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function accountItems(): array
    {
        return [
            [
                'label' => 'My profile',
                'url' => route('profile.show'),
                'icon' => 'far fa-user',
                'group' => 'Account',
                'keywords' => 'profile account details',
                'sidebar' => false,
                'route_is' => 'profile.show',
            ],
            [
                'label' => 'Update profile',
                'url' => route('profile.edit'),
                'icon' => 'far fa-edit',
                'group' => 'Account',
                'keywords' => 'edit profile password phone',
                'sidebar' => false,
                'route_is' => 'profile.edit',
            ],
        ];
    }
}
