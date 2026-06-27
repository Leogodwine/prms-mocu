# MoCU-PRMS

Integrated **Project and Research Management System** for Moshi Co-operative University (MoCU).

## Requirements

- PHP 8.2+ with extensions: `pdo_mysql`, `mbstring`, `openssl`, `gd`, `fileinfo`
- MySQL 8+ (recommended for production)
- Composer 2.x
- Node.js 18+ (for front-end asset build, if used)

Optional integrations: Ollama (similarity analysis), ONLYOFFICE (online Word editing).

## Production deployment

### 1. Clone and install

```bash
git clone <repository-url> prms
cd prms
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

### 2. Configure `.env`

Set at minimum:

| Variable | Purpose |
|----------|---------|
| `APP_ENV=production` | Production mode |
| `APP_DEBUG=false` | Never `true` on live host |
| `APP_URL` | Public URL of the application |
| `DB_*` | MySQL connection |
| `PRMS_ADMIN_EMAIL` | First admin login email |
| `PRMS_ADMIN_PASSWORD` | Strong password (12+ characters) |
| `MAIL_*` | Outbound mail for notifications |

### 3. Database (mandatory seed data only)

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
```

`db:seed` loads **only mandatory bootstrap data**:

- **Roles & permissions** (`RoleSeeder`)
- **Workflow stages** (`ProjectStageSeeder`) — required before students can submit
- **Initial admin account** (`AdminUserSeeder`) — from `PRMS_ADMIN_*` env vars

It does **not** seed demo users, sample projects, faculties, or rubrics. Create those through the admin UI or SIS sync after go-live.

### 4. Optimize for production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Run a queue worker for background jobs (similarity analysis, showcase parsing):

```bash
php artisan queue:work --tries=3
```

Schedule cron (every minute):

```bash
* * * * * cd /path/to/prms && php artisan schedule:run >> /dev/null 2>&1
```

### 5. Web server

Point the document root to `public/`. Ensure `storage/` and `bootstrap/cache/` are writable.

## Local development

```bash
composer install
cp .env.example .env
# Set APP_ENV=local, APP_DEBUG=true, DB_* and PRMS_ADMIN_PASSWORD
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

Optional demo data (local/staging only):

```bash
php artisan db:seed --class=DevelopmentSeeder
```

## Security notes

- Never commit `.env` or real credentials to GitHub.
- Change the admin password after first login if `PRMS_ADMIN_MUST_CHANGE_PASSWORD=true`.
- Keep `APP_DEBUG=false` on the live host.
- Use HTTPS in production (`APP_URL` must match).

## License

MIT (Laravel framework components). Application code — MoCU.
