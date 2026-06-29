<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\CoordinatorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ForcePasswordController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Controllers\ProjectContributorController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectSimilarityController;
use App\Http\Controllers\PresentationConsentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicResearchController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\SubmissionEditorController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubmissionShowcaseController;
use App\Http\Controllers\AdminAuditController;
use App\Http\Controllers\AdminSisController;
use App\Http\Controllers\AdminBackupController;
use App\Http\Controllers\AdminSystemHealthController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminAcademicConfigurationController;
use App\Http\Controllers\AdminConfigurationController;
use App\Http\Controllers\HodController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// FR-11: Public Research Access Portal (no authentication required).
Route::prefix('research')->name('public.research.')->middleware('throttle:public-search')->group(function () {
    Route::match(['get', 'post'], '/', [PublicResearchController::class, 'index'])->name('index');
    Route::get('/{project}/screenshot', [PublicResearchController::class, 'screenshot'])
        ->whereNumber('project')
        ->name('screenshot');
    Route::get('/{project}/showcase', [PublicResearchController::class, 'showcase'])
        ->whereNumber('project')
        ->name('showcase');
    Route::get('/{project}/source-download', [PublicResearchController::class, 'sourceDownload'])
        ->whereNumber('project')
        ->name('source-download');
    Route::get('/{project}', [PublicResearchController::class, 'show'])
        ->whereNumber('project')
        ->name('show');
    Route::get('/{project}/download', [PublicResearchController::class, 'download'])
        ->whereNumber('project')
        ->name('download');
    Route::get('/{project}/citation/{style}', [PublicResearchController::class, 'citation'])
        ->whereNumber('project')
        ->whereIn('style', ['apa', 'mla', 'chicago', 'harvard'])
        ->name('citation');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:login')->name('login.store');
    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->middleware('throttle:password-reset')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/force-password-change', [ForcePasswordController::class, 'edit'])->name('password.force.edit');
    Route::put('/force-password-change', [ForcePasswordController::class, 'update'])->name('password.force.update');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/bulk-read', [NotificationController::class, 'bulkMarkRead'])->name('notifications.bulk-read');
    Route::post('/notifications/bulk-delete', [NotificationController::class, 'bulkDestroy'])->name('notifications.bulk-delete');
    Route::get('/notifications/bulk-delete', fn () => redirect()->route('notifications.index'));
    Route::get('/notifications/bulk-read', fn () => redirect()->route('notifications.index'));
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::put('/notifications/preferences', [NotificationPreferenceController::class, 'update'])->name('notifications.preferences.update');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // FR-02: Project & research proposal tracking workspace.
    Route::get('/projects', [ProjectController::class, 'index'])
        ->middleware('role:project_student,research_student,normal_student,supervisor')
        ->name('projects.index');
    Route::get('/projects/create', [ProjectController::class, 'create'])
        ->middleware('role:project_student,research_student,normal_student')
        ->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])
        ->middleware('role:project_student,research_student,normal_student')
        ->name('projects.store');
    Route::post('/projects/problem-proposal', [ProjectController::class, 'storeProblemProposal'])
        ->middleware('role:project_student,research_student,normal_student')
        ->name('projects.problem-proposal.store');
    Route::get('/projects/{researchProject}', [ProjectController::class, 'show'])
        ->whereNumber('researchProject')
        ->name('projects.show');
    Route::post('/projects/{researchProject}/contributors', [ProjectContributorController::class, 'store'])
        ->whereNumber('researchProject')
        ->middleware('role:project_student,normal_student')
        ->name('projects.contributors.store');
    Route::delete('/projects/{researchProject}/contributors/{contributor}', [ProjectContributorController::class, 'destroy'])
        ->whereNumber(['researchProject', 'contributor'])
        ->middleware('role:project_student,normal_student')
        ->name('projects.contributors.destroy');

    // User profile (view, edit, update).
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'password.changed', 'role:coordinator'])->prefix('coordinator')->name('coordinator.')->group(function () {
    Route::match(['get', 'post'], '/', [CoordinatorController::class, 'index'])->name('index');
    Route::get('/deadlines', [CoordinatorController::class, 'manageDeadlines'])->name('deadlines');
    Route::post('/deadlines', [CoordinatorController::class, 'storeDeadline'])->name('deadlines.store');
    Route::put('/deadlines/{deadline}', [CoordinatorController::class, 'updateDeadline'])->name('deadlines.update');
    Route::delete('/deadlines/{deadline}', [CoordinatorController::class, 'destroyDeadline'])->name('deadlines.destroy');
    Route::post('/groups', [CoordinatorController::class, 'storeGroup'])->name('groups.store');
    Route::post('/groups/auto-form', [CoordinatorController::class, 'autoGroupStudents'])->name('groups.auto-form');
    Route::post('/groups/auto-assign', [CoordinatorController::class, 'autoAssignSupervisors'])->name('groups.auto-assign');
    Route::post('/assign-supervisor', [CoordinatorController::class, 'assignSupervisor'])->name('supervisor.assign');
    
    // Grading scheme management (coordinator)
    Route::get('/rubrics', [CoordinatorController::class, 'rubrics'])->name('rubrics.index');
    Route::get('/rubrics/create', [CoordinatorController::class, 'createRubric'])->name('rubrics.create');
    Route::post('/rubrics', [CoordinatorController::class, 'storeRubric'])->name('rubrics.store');

    Route::match(['get', 'post'], '/submissions', [CoordinatorController::class, 'submissions'])->name('submissions');
    Route::get('/submissions/consent/{submission}/sign', [CoordinatorController::class, 'consentSign'])->name('submissions.consent.sign');
    Route::post('/submissions/consent/{submission}/sign', [CoordinatorController::class, 'consentSignStore'])->name('submissions.consent.sign.store');
    Route::post('/submissions/consent/{submission}/approve', [CoordinatorController::class, 'approveConsentSubmission'])->name('submissions.consent.approve');
    Route::get('/submissions/consent/{submission}/pdf', [CoordinatorController::class, 'consentPdf'])->name('submissions.consent.pdf');
    Route::post('/submissions/{submission}/approve', [CoordinatorController::class, 'approveSubmission'])->name('submissions.approve');
});

Route::middleware(['auth', 'password.changed', 'role:supervisor'])->prefix('supervisor')->name('supervisor.')->group(function () {
    Route::match(['get', 'post'], '/', [SupervisorController::class, 'index'])->name('index');
    Route::get('/logs/create', [SupervisorController::class, 'createLog'])->name('logs.create');
    Route::get('/logs', [SupervisorController::class, 'logs'])->name('logs');
    Route::post('/logs', [SupervisorController::class, 'storeLog'])->name('logs.store');
    Route::get('/workload', [SupervisorController::class, 'workload'])->name('workload');
    Route::post('/submissions/{submission}/review', [SupervisorController::class, 'review'])->name('review');

    // FR-04: grading-scheme-based evaluation flow.
    Route::get('/submissions/{submission}/evaluate', [SupervisorController::class, 'evaluate'])->name('evaluate');
    Route::post('/submissions/{submission}/evaluate', [SupervisorController::class, 'storeEvaluation'])->name('evaluate.store');

    Route::get('/presentation-consent/{submission}', [PresentationConsentController::class, 'supervisorSign'])
        ->name('presentation-consent.sign');
    Route::post('/presentation-consent/{submission}', [PresentationConsentController::class, 'supervisorSignStore'])
        ->name('presentation-consent.sign.store');
    Route::post('/presentation-consent/{submission}/preview-pdf', [PresentationConsentController::class, 'previewPdf'])
        ->name('presentation-consent.preview-pdf');
    Route::get('/presentation-consent/{submission}/pdf', [PresentationConsentController::class, 'pdf'])
        ->name('presentation-consent.pdf');
});

Route::middleware(['auth', 'password.changed', 'role:project_student,research_student,normal_student,coordinator'])->prefix('student')->name('student.')->group(function () {
    Route::get('/', [StudentController::class, 'index'])->name('index');
});

Route::middleware(['auth', 'password.changed', 'role:project_student,research_student,normal_student,coordinator,supervisor,hod,admin'])
    ->get('/presentation-consent', [PresentationConsentController::class, 'show'])
    ->name('presentation-consent.show');
Route::middleware(['auth', 'password.changed', 'role:project_student,research_student,normal_student,coordinator,supervisor,hod,admin'])
    ->get('/presentation-consent/download', [PresentationConsentController::class, 'download'])
    ->name('presentation-consent.download');

// Submission file access (download + inline preview): authorization is
// enforced inside the controller so the route stays open to any
// authenticated PRMS role; the controller only allows owners, the
// assigned supervisor, and oversight roles to read the file.
Route::middleware(['auth', 'password.changed', 'role:project_student,research_student,normal_student,coordinator,supervisor,hod,admin'])
    ->prefix('student')->name('student.')->group(function () {
        Route::get('/submissions/{submission}/download', [StudentController::class, 'download'])->name('submissions.download');
        Route::get('/submissions/{submission}/preview', [StudentController::class, 'preview'])->name('submissions.preview');
        Route::get('/submissions/screenshots/{screenshot}', [StudentController::class, 'interfaceScreenshot'])->name('submissions.interface-screenshot');
        Route::get('/submissions/{submission}/screenshot', [StudentController::class, 'screenshot'])->name('submissions.screenshot');
        Route::get('/submissions/{submission}/documentation', [StudentController::class, 'documentation'])->name('submissions.documentation');
        Route::get('/submissions/{submission}/showcase', [SubmissionShowcaseController::class, 'show'])->name('submissions.showcase');
        Route::get('/submissions/{submission}/showcase-meta', [SubmissionShowcaseController::class, 'meta'])->name('submissions.showcase-meta');
        Route::get('/submissions/{submission}/editor', [SubmissionEditorController::class, 'edit'])->name('submissions.editor');
    });

Route::get('/student/submissions/{submission}/onlyoffice-serve', [SubmissionEditorController::class, 'serve'])
    ->name('student.submissions.onlyoffice-serve')
    ->middleware('signed');

Route::post('/student/submissions/{submission}/onlyoffice-callback', [SubmissionEditorController::class, 'callback'])
    ->name('student.submissions.onlyoffice-callback')
    ->middleware('signed');

Route::middleware(['auth', 'password.changed', 'role:project_student,research_student,normal_student'])->prefix('student')->name('student.')->group(function () {
    Route::post('/submissions', [StudentController::class, 'storeSubmission'])->name('submissions.store');
    Route::post('/submissions/create-blank', [SubmissionEditorController::class, 'createBlank'])->name('submissions.create-blank');
    Route::post('/submissions/{submission}/submit-to-coordinator', [StudentController::class, 'submitToCoordinator'])->name('submissions.submit-to-coordinator');
});

Route::middleware(['auth', 'password.changed', 'role:project_student,research_student,normal_student,coordinator,supervisor,hod'])
    ->prefix('archive')
    ->name('archive.')
    ->group(function () {
        Route::match(['get', 'post'], '/', [ArchiveController::class, 'index'])->name('index');
        Route::get('/export', [ArchiveController::class, 'export'])->name('export');
    });

Route::middleware(['auth', 'password.changed', 'role:admin,coordinator,hod'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/coordinator', [ReportController::class, 'coordinator'])->name('coordinator');
    Route::match(['get', 'post'], '/coordinator/materials', [ReportController::class, 'coordinatorMaterials'])->name('coordinator.materials');
    Route::get('/coordinator/export', [ReportController::class, 'coordinatorExport'])->name('coordinator.export');
});

Route::middleware(['auth', 'password.changed', 'role:supervisor'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/supervisor', [ReportController::class, 'supervisor'])->name('supervisor');
    Route::get('/supervisor/export', [ReportController::class, 'supervisorExport'])->name('supervisor.export');
});

Route::middleware(['auth', 'password.changed', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::post('/users/bulk-import', [AdminUserController::class, 'bulkImport'])->name('users.bulk-import');
    Route::post('/users/bulk-delete', [AdminUserController::class, 'bulkDestroy'])->name('users.bulk-delete');
    Route::get('/users/bulk-delete', fn () => redirect()->route('admin.users.index'));
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
});

Route::middleware(['auth', 'password.changed', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/system-health', [AdminSystemHealthController::class, 'index'])->name('system-health');
    Route::post('/system-health/maintenance/enable', [AdminSystemHealthController::class, 'enableMaintenance'])->name('system-health.maintenance.enable');
    Route::post('/system-health/maintenance/disable', [AdminSystemHealthController::class, 'disableMaintenance'])->name('system-health.maintenance.disable');
    Route::post('/system-health/maintenance/task', [AdminSystemHealthController::class, 'runMaintenanceTask'])->name('system-health.maintenance.task');
    Route::post('/system-health/heartbeat', [AdminSystemHealthController::class, 'heartbeat'])->name('system-health.heartbeat');
    Route::post('/system-health/failed-jobs/clear', [AdminSystemHealthController::class, 'clearFailedJobs'])->name('system-health.failed-jobs.clear');
    Route::post('/system-health/failed-jobs/{id}/retry', [AdminSystemHealthController::class, 'retryFailedJob'])->name('system-health.failed-jobs.retry');
    Route::get('/backups', [AdminBackupController::class, 'index'])->name('backups.index');
    Route::post('/backups', [AdminBackupController::class, 'store'])->name('backups.store');
    Route::put('/backups/settings', [AdminBackupController::class, 'updateSettings'])->name('backups.settings');
    Route::delete('/backups/{backup}', [AdminBackupController::class, 'destroy'])->name('backups.destroy');
    Route::get('/backups/{backup}/download', [AdminBackupController::class, 'download'])->name('backups.download');
    Route::post('/backups/{backup}/restore', [AdminBackupController::class, 'restore'])->name('backups.restore');
    Route::get('/audit', [AdminAuditController::class, 'index'])->name('audit');
    Route::get('/sis-sync', [AdminSisController::class, 'index'])->name('sis-sync');
    Route::post('/sis-sync/run', [AdminSisController::class, 'runSync'])->name('sis-sync.run');
    Route::post('/sis-sync/backfill-gender', [AdminSisController::class, 'runGenderBackfill'])->name('sis-sync.backfill-gender');
    Route::get('/configuration', [AdminConfigurationController::class, 'index'])->name('configuration.index');
    Route::put('/configuration', [AdminConfigurationController::class, 'update'])->name('configuration.update');
    Route::put('/configuration/workflow-defaults', [AdminConfigurationController::class, 'updateWorkflowDefaults'])->name('configuration.workflow-defaults');
    Route::put('/configuration/programmes/{program}', [AdminConfigurationController::class, 'updateProgramme'])->name('configuration.programmes.update');
    Route::post('/configuration/department-rules', [AdminConfigurationController::class, 'storeDepartmentRule'])->name('configuration.department-rules.store');
    Route::put('/configuration/department-rules/{departmentWorkflowRule}', [AdminConfigurationController::class, 'updateDepartmentRule'])->name('configuration.department-rules.update');
    Route::delete('/configuration/department-rules/{departmentWorkflowRule}', [AdminConfigurationController::class, 'destroyDepartmentRule'])->name('configuration.department-rules.destroy');
    Route::post('/configuration/reevaluate-workflows', [AdminConfigurationController::class, 'reevaluateWorkflows'])->name('configuration.reevaluate-workflows');
    Route::match(['get', 'post'], '/similarities', [ProjectSimilarityController::class, 'index'])->name('similarities.index');
    Route::post('/projects/{researchProject}/check-similarity', [ProjectSimilarityController::class, 'rerun'])
        ->whereNumber('researchProject')
        ->name('projects.similarity.rerun');
});

Route::middleware(['auth', 'password.changed', 'role:admin,hod'])->prefix('admin')->name('admin.')->group(function () {
    Route::prefix('academic-configuration')->name('academic-configuration.')->group(function () {
        Route::get('/', [AdminAcademicConfigurationController::class, 'index'])->name('index');
        Route::get('/departments', [AdminAcademicConfigurationController::class, 'departments'])->name('departments.index');
        Route::post('/departments', [AdminAcademicConfigurationController::class, 'storeDepartment'])->name('departments.store');
        Route::put('/departments/{department}', [AdminAcademicConfigurationController::class, 'updateDepartment'])->name('departments.update');
        Route::delete('/departments/{department}', [AdminAcademicConfigurationController::class, 'destroyDepartment'])->name('departments.destroy');
        Route::get('/programmes', [AdminAcademicConfigurationController::class, 'programmes'])->name('programmes.index');
        Route::post('/programmes', [AdminAcademicConfigurationController::class, 'storeProgramme'])->name('programmes.store');
        Route::put('/programmes/{program}', [AdminAcademicConfigurationController::class, 'updateProgramme'])->name('programmes.update');
        Route::delete('/programmes/{program}', [AdminAcademicConfigurationController::class, 'destroyProgramme'])->name('programmes.destroy');
        Route::get('/levels', [AdminAcademicConfigurationController::class, 'levels'])->name('levels.index');
        Route::put('/levels/{academicLevelSetting}', [AdminAcademicConfigurationController::class, 'updateLevel'])->name('levels.update');
        Route::get('/preview', [AdminAcademicConfigurationController::class, 'preview'])->name('preview.index');
        Route::post('/preview/check', [AdminAcademicConfigurationController::class, 'checkEligibility'])->name('preview.check');
        Route::post('/reevaluate', [AdminAcademicConfigurationController::class, 'reevaluate'])->name('reevaluate');
    });
});

Route::middleware(['auth', 'password.changed', 'role:hod'])->prefix('hod')->name('hod.')->group(function () {
    Route::get('/', [HodController::class, 'index'])->name('index');
    Route::get('/students', [HodController::class, 'students'])->name('students.index');
    Route::put('/students/{user}', [HodController::class, 'updateStudent'])->name('students.update');
});
