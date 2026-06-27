# MoCU PRMS — Technology Stack

This document describes the technologies used to build and run the **Project & Research Management System (PRMS)** for Moshi Co-operative University (MoCU).

**Related:** `composer.json`, `package.json`, `.env`, `docs/NFR-Implementation-Matrix.md`

---

## 1. Overview

PRMS is a **monolithic Laravel web application** with server-rendered Blade views, a Bootstrap-based admin UI (KaiAdmin), and optional local services for document editing and AI-assisted checks.

```
Browser  →  Laravel (PHP)  →  MySQL
                ↓
         Local / public storage (uploads, PDFs)
                ↓ (optional)
         ONLYOFFICE Document Server, Ollama
```

---

## 2. Backend

| Layer | Technology | Notes |
|-------|------------|--------|
| Language | **PHP 8.2+** | Required by `composer.json` |
| Framework | **Laravel 12** | Routing, auth, Eloquent ORM, queues, mail, validation |
| Database | **MySQL** | Primary store; configured via `DB_*` in `.env` |
| Templates | **Blade** | `resources/views/` |
| PDF generation | **dompdf** | `barryvdh/laravel-dompdf` — consent letters, downloadable forms |
| Sessions | **Database** | `SESSION_DRIVER=database` |
| Cache | **Database** | `CACHE_STORE=database` |
| Queue | **Database** | `QUEUE_CONNECTION=database` — showcase analysis, similarity jobs |
| File storage | **Local disk** | `FILESYSTEM_DISK=local`; public uploads under `storage/app/public` |

### Key application directories

| Path | Purpose |
|------|---------|
| `app/Http/Controllers/` | HTTP request handling |
| `app/Models/` | Eloquent models |
| `app/Support/` | Domain helpers (stage progress, publication, eligibility) |
| `app/Services/` | ONLYOFFICE, Ollama, similarity, showcase |
| `app/Jobs/` | Queued background work |
| `database/migrations/` | Schema definitions |
| `routes/web.php` | Web routes and middleware |

---

## 3. Frontend & UI

| Layer | Technology | Notes |
|-------|------------|--------|
| Admin theme | **KaiAdmin** | Branded assets in `public/vendor/prms-mocu/` |
| CSS framework | **Bootstrap 5** | Loaded from KaiAdmin assets |
| JavaScript | **jQuery 3.7.1** | KaiAdmin plugins, modals, sidebar |
| Icons | **Font Awesome**, **Google Material Symbols** | Workspace sidebar, actions |
| Typography | **Open Sans** | Google Fonts |
| Custom CSS | `public/css/prms-theme.css`, `prms-kaiadmin-bridge.css` | MoCU branding and UX tokens |
| Build tooling | **Vite 7**, **Tailwind CSS 4**, **Axios** | Laravel default pipeline (`package.json`); main UI still uses KaiAdmin/Bootstrap assets directly |

### Layout entry points

- Authenticated app: `resources/views/layouts/app.blade.php`
- Public research repository: `resources/views/layouts/public.blade.php`
- Print/PDF views: `resources/views/layouts/print.blade.php`

---

## 4. Optional integrations

### ONLYOFFICE Document Server

| Item | Detail |
|------|--------|
| Purpose | In-browser editing of Word submissions |
| Package | Docker image `onlyoffice/documentserver` |
| Compose file | `docker-compose.onlyoffice.yml` |
| Config | `config/onlyoffice.php`, `.env` (`ONLYOFFICE_*`) |
| Service | `app/Services/OnlyOfficeService.php` |

The document server runs on host port **8080** by default. Laravel must be reachable from the container (e.g. `php artisan serve --host=0.0.0.0 --port=8000`).

### Ollama (local LLM)

| Item | Detail |
|------|--------|
| Purpose | Project similarity checks, submission showcase summaries |
| Default model | **Mistral** (`ollama pull mistral`) |
| Config | `config/ollama.php`, `.env` (`OLLAMA_*`) |
| Service | `app/Services/Ollama/OllamaClient.php` |

Runs locally at `http://127.0.0.1:11434` when enabled.

---

## 5. Development & testing

| Tool | Purpose |
|------|---------|
| **Composer** | PHP dependency management |
| **npm** | Frontend build (Vite) |
| **PHPUnit 11** | Automated tests (`tests/`) |
| **Laravel Pint** | PHP code style |
| **Laravel Pail** | Log tailing in dev |
| **Laravel Sail** | Optional Docker dev environment |
| **Faker** | Seed and test data |

### Common local commands

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
npm run dev          # Vite (optional)
php artisan queue:listen
```

On Windows/XAMPP, MySQL is typically provided by XAMPP; the app is often served with `php artisan serve` on port 8000.

---

## 6. Production considerations

| Area | Current dev setup | Production recommendation |
|------|-------------------|---------------------------|
| Web server | `artisan serve` / XAMPP | Apache or Nginx + PHP-FPM |
| Database | MySQL on localhost | Managed MySQL with backups |
| Queue worker | Manual `queue:listen` | Supervisor or systemd worker |
| Cache / sessions | Database | Redis for scale |
| Mail | `log` driver | SMTP or transactional mail service |
| ONLYOFFICE | Local Docker | Dedicated document server with JWT |
| File storage | Local disk | S3-compatible storage if multi-server |

---

## 7. Security & auth (built-in Laravel)

- Session-based authentication
- Role middleware (`EnsureUserRole`, `EnsurePasswordChanged`)
- CSRF protection on forms
- Password hashing (bcrypt)
- Audit logging (`app/Support/Audit.php`)

---

## 8. Version summary

| Component | Version (from project files) |
|-----------|----------------------------|
| PHP | ^8.2 |
| Laravel | ^12.0 |
| Vite | ^7.0 |
| Tailwind CSS | ^4.0 |
| PHPUnit | ^11.5 |
| dompdf (Laravel wrapper) | ^3.1 |

---

*Last updated: June 2026 — align with `composer.json` and `package.json` when upgrading dependencies.*
