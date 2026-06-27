# MoCU PRMS — Non-Functional Requirements (NFR) Matrix

This document maps non-functional requirements to implementation in the Project & Research Management System (PRMS). It supports audits, maintenance, and continuous UX improvement.

**Related:** `docs/Functional Requirements Document.txt` (FRD §NFR)

---

## 1. Readability & UX

| Requirement | Target | Implementation | Status |
|-------------|--------|----------------|--------|
| Consistent typography | WCAG-friendly line height, readable body text | `public/css/prms-theme.css` design tokens (`--prms-lh-base`, `--prms-text`) | Implemented |
| Unified feedback | Success/error/warning alerts with icons + dismiss | `resources/views/components/prms-flash-messages.blade.php`, included in `layouts/app.blade.php` and `layouts/public.blade.php` | Implemented |
| Progressive disclosure | Long repository descriptions collapse with expand | `resources/views/partials/public-publication-description.blade.php` | Implemented |
| Responsive grids | Project/public cards adapt 1→3 columns | Bootstrap grid in repository & student project views | Implemented |
| Plain-language errors | Validation lists, login lockout messages | Controllers + flash component | Implemented |

**Completed:** Duplicate per-page alert markup removed from role workspaces; global `<x-prms-flash-messages />` handles success, warning, error, info, and preformatted sync output.

---

## 2. Scalability & Performance

| Requirement | Target | Implementation | Status |
|-------------|--------|----------------|--------|
| Paginated lists | Avoid loading entire datasets | Laravel paginator + `prms-bootstrap-5` pagination view | Implemented |
| Indexed audit/login tables | Fast admin queries | `audit_logs`, `login_history` migrations | Implemented |
| Queue for heavy jobs | Showcase analysis, similarity checks | `app/Jobs/*`, `routes/console.php` schedule | Implemented |
| Public search throttle | Prevent abuse | `throttle:public-search` on `/research` routes | Implemented |
| Caching strategy | Config/session/cache tables | Laravel default `database` cache driver | Partial |

**Improvement backlog:** Add DB indexes on `audit_logs.created_at`, `project_submissions.stage`; introduce Redis for cache/queue in production.

---

## 3. Maintainability

| Requirement | Target | Implementation | Status |
|-------------|--------|----------------|--------|
| Domain logic in Support classes | Single source of stage/workflow rules | `app/Support/StudentStageProgress.php`, `RepositoryPublication.php`, etc. | Implemented |
| Shared Blade components | Reuse UI patterns | `components/prms-greeting-banner`, `prms-flash-messages`, partials | Partial |
| Migrations by domain | Traceable schema evolution | `database/migrations/` (45+ files) | Implemented |
| Design system | Central CSS variables | `public/css/prms-theme.css` (~569 `--prms-*` tokens) | Implemented |

**Improvement backlog:** Introduce Form Request classes; consolidate validation; add `README.md` developer onboarding.

**Completed:** Form Request classes for student submissions, admin users, coordinator rubrics/deadlines (`app/Http/Requests/*`).

---

## 4. Reliability & Availability

| Requirement | Target | Implementation | Status |
|-------------|--------|----------------|--------|
| Health check | Uptime monitoring | Laravel `/up` endpoint (`bootstrap/app.php`) | Implemented |
| System health dashboard | Queue failures, SIS sync status | `AdminSystemHealthController` | Implemented |
| Scheduled maintenance jobs | Nightly similarity scan | `projects:check-similarities` at 02:30 | Implemented |
| Graceful degradation | Public portal without auth | `PublicResearchController` null-safe document resolution | Implemented |
| Backup & recovery | Weekly DB + file backup | `php artisan prms:backup`, scheduled Sundays 03:00 | Implemented |

**Recovery procedure:**
1. Stop web server.
2. Restore `storage/backups/{timestamp}/database.sql` into MySQL.
3. Copy `storage/backups/{timestamp}/storage-public/` to `storage/app/public/`.
4. Run `php artisan config:clear && php artisan view:clear`.
5. Verify `/up` and admin system health.

---

## 5. Expandability (Extensibility)

| Requirement | Target | Implementation | Status |
|-------------|--------|----------------|--------|
| New project stages | Seed + migration pattern | `ProjectStageSeeder`, stage migrations | Implemented |
| SIS integration | External student import | `SyncSisStudentsCommand`, `AdminSisController` | Implemented |
| OnlyOffice / Word editing | Optional document editor | `OnlyOfficeService`, editor routes | Implemented |
| Public repository bridge | Internal submissions → public portal | `PublicPortalPublication.php` | Implemented |
| RBAC tables | Future fine-grained permissions | `roles`, `user_roles` migrations | Partial (routes use `users.role`) |

---

## 6. Security

| Requirement | Target | Implementation | Status |
|-------------|--------|----------------|--------|
| Authentication | Session-based login | `AuthController`, `auth` middleware | Implemented |
| Role-based access | Route middleware | `EnsureUserRole`, `password.changed` | Implemented |
| CSRF protection | All mutating forms | Laravel CSRF (OnlyOffice callback exempt) | Implemented |
| File authorization | Per-submission access | `SubmissionFileAccess::authorize()` | Implemented |
| Login rate limit | Brute-force mitigation | `throttle:login` (8/min), IP lockout after 5 failures/15min | Implemented |
| Password reset limit | Abuse prevention | `throttle:password-reset` (5/hour) | Implemented |
| Security headers | XSS/clickjacking baseline | `SecurityHeadersMiddleware` | Implemented |
| Audit trail | Sensitive actions logged | `App\Support\Audit`, `audit_logs` table | Implemented |

**Improvement backlog:** MFA, virus scanning on uploads, Laravel Policies, CSP hardening, admin re-auth for destructive actions.

---

## 7. Accuracy

| Requirement | Target | Implementation | Status |
|-------------|--------|----------------|--------|
| Stage-gate rules | Enforced upload order | `StudentStageProgress::canUploadStage()` | Implemented |
| Coordinator eligibility | Only complete documents | `canSubmitToCoordinator()` | Implemented |
| Repository publication rules | Consent-gated project release | `RepositoryPublication.php` | Implemented |
| Citation export | APA/MLA/Chicago/Harvard | `PublicResearchController::buildCitations()` | Implemented |
| Similarity detection | Scheduled plagiarism scan | `CheckProjectSimilaritiesCommand` | Implemented |

---

## 8. Interoperability

| Requirement | Target | Implementation | Status |
|-------------|--------|----------------|--------|
| SIS student sync | External CSV/API import | `SyncSisStudentsCommand` | Implemented |
| OnlyOffice Document Server | In-browser Word editing | `WordDocumentController`, signed URLs | Implemented |
| Public research portal | Unauthenticated read access | `/research` routes | Implemented |
| Standard file formats | PDF, DOCX, ZIP, PPT | Upload validation per stage | Implemented |

---

## 9. Accessibility

| Requirement | Target | Implementation | Status |
|-------------|--------|----------------|--------|
| Skip links | Keyboard users skip chrome | `.prms-skip-link` in all main layouts | Implemented |
| ARIA landmarks | `role="main"`, labelled nav | `layouts/app.blade.php`, `public.blade.php` | Implemented |
| Focus visibility | `:focus-visible` outlines | `prms-theme.css` | Implemented |
| Reduced motion | Respect user preference | `@media (prefers-reduced-motion: reduce)` | Implemented |
| Live regions | Screen reader feedback | `aria-live="polite"` on flash component | Implemented |
| Accessible pagination | `aria-label` on page links | `vendor/pagination/prms-bootstrap-5.blade.php` | Implemented |

**Improvement backlog:** axe/pa11y CI checks.

**Completed:** OnlyOffice editor page — skip link, landmarks, `aria-live` status, focus styles, error fallbacks with return link.

---

## 10. Flexibility

| Requirement | Target | Implementation | Status |
|-------------|--------|----------------|--------|
| Multi-track workspaces | Proposal / research / project | `StudentController`, stage stepper | Implemented |
| Group & individual submissions | Shared group visibility | `project_group_id` on submissions | Implemented |
| Configurable system settings | Admin configuration UI | `AdminConfigurationController`, `system_configurations` | Implemented |
| Filter persistence | Session-based list filters | `PrmsListFilters` helper | Implemented |
| Layout chrome colors | KaiAdmin logo/navbar/sidebar swatches | `prms-customizer.js`, settings panel | Implemented |

---

## 11. Auditability

| Requirement | Target | Implementation | Status |
|-------------|--------|----------------|--------|
| User action logging | Who/what/when/IP | `Audit::log()`, `audit_logs` | Implemented |
| Login history | Success/failure tracking | `login_history`, `AuthController` | Implemented |
| Admin audit UI | Searchable log viewer with user names & metadata | `admin/audit.blade.php`, eager-loaded `user` relation | Implemented |
| Archive export logging | Track data exports | `ArchiveController::export` + `Audit::log` | Implemented |

**Logged actions include:** auth, submissions, supervisor review, coordinator actions, admin user changes, configuration updates.

---

## 12. Backup & Recovery

| Command | Schedule | Retention |
|---------|----------|-----------|
| `php artisan prms:backup` | Sundays 03:00 | 14 snapshots (configurable `--keep`) |

**Artifacts per backup:**
- `database.sql` — MySQL dump (requires `mysqldump`; set `MYSQLDUMP_PATH` in `.env` on Windows/XAMPP)
- `storage-public/` — copy of `storage/app/public`
- `manifest.json` — metadata for restore verification

---

## 13. Operational Commands

| Command | Purpose |
|---------|---------|
| `php artisan prms:backup` | Database + public storage backup |
| `php artisan repository:sync-publications` | Sync published work to public portal |
| `php artisan sis:sync-students` | SIS interoperability |
| `php artisan projects:check-similarities` | Scheduled integrity/similarity scan |

---

## 14. UX/UI Improvement Roadmap (Priority)

1. ~~**High** — Replace duplicate alerts with `<x-prms-flash-messages />` across all views.~~ **Done**
2. ~~**High** — Form Request classes for student upload, admin user, coordinator actions.~~ **Done**
3. ~~**Medium** — Admin audit: show user name instead of raw `user_id`.~~ **Done**
4. ~~**Medium** — Accessibility pass on OnlyOffice editor iframe page.~~ **Done**
5. ~~**Medium** — Loading states / skeleton UI on repository search.~~ **Done**

---

*Last updated: June 2026 — aligned with Complete System stage, public repository grid, and NFR hardening pass.*
