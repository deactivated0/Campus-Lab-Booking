
# Campus Lab Booking (Project)

Brief
- Laravel 12 backend with Inertia + Vue 3 frontend. The app provides lab and equipment booking, issues time-limited QR tokens for tablet kiosks, and records automated usage logs.

Quick repo map
- Routes: [routes/web.php](routes/web.php) and [routes/auth.php](routes/auth.php)
- Backend controllers: [app/Http/Controllers](app/Http/Controllers)
- Models: [app/Models](app/Models) — `User`, `Booking`, `Equipment`, `Lab`, `QRToken`, `UsageLog`
- Frontend: [resources/js](resources/js) (Inertia + Vue pages/components)
- Exports: [app/Exports/UsageLogsExport.php](app/Exports/UsageLogsExport.php)

Prerequisites (local dev)
- PHP >= 8.2, Composer
- Node.js (16+), npm
- MySQL (the project uses a `project` database by default) — Laragon on Windows recommended

Install and run (local)
1. Install PHP dependencies:

```bash
composer install
```

2. Install JS dependencies and build:

```bash
npm ci
npm run dev
```

3. Copy environment and configure database credentials:

```bash
cp .env.example .env
# edit .env to set DB_DATABASE, DB_USERNAME, DB_PASSWORD, APP_URL
php artisan key:generate
```

4. Create the database and run migrations/seeds:

```bash
php artisan migrate --force
php artisan db:seed
```

Database dump (MySQL)
- To create a full MySQL dump (structure, data, triggers, routines, events), run locally (replace credentials as needed):

If `mysqldump` is in your PATH:

```powershell
mysqldump -u root -p --single-transaction --routines --events --triggers project > database/project_dump.sql
```

If you use Laragon on Windows, the binary path may be like:

```powershell
& 'C:\laragon\bin\mysql\mysql-8.0.33\bin\mysqldump.exe' -u root -p --single-transaction --routines --events --triggers project > database/project_dump.sql
```

Compress the dump:

```powershell
gzip -c database/project_dump.sql > database/project_dump.sql.gz
```

Notes: I couldn't run `mysqldump` from the environment here — please run the above on your machine and then I can verify and add `database/project_dump.sql` to the repo.

Features & checklist mapping
- Authentication: Laravel auth + Socialite Google login ([app/Http/Controllers/AuthController.php](app/Http/Controllers/AuthController.php))
- Authorization: Spatie permissions used and role middleware in routes ([composer.json](composer.json))
- Multi-user + roles: Admin, LabStaff, Student
- Models: Booking, Equipment, Lab, QRToken, UsageLog, User
- Controllers: BookingController, KioskController, DashboardController, EquipmentController, ReportController, etc.
- Frontend: Inertia + Vue 3, Tailwind, FullCalendar, QR libs, charts, drag/drop
- Kiosk/QR workflow: `KioskController` + `QRToken` + `UsageLog`
- Exports: `UsageLogsExport` (uses Maatwebsite Excel if installed; fallback CSV exists)

Third-party libraries (high level)
- Backend (composer): `inertiajs/inertia-laravel`, `laravel/socialite`, `spatie/laravel-permission`, `tightenco/ziggy`
- Frontend (npm): `vue`, `@inertiajs/vue3`, `tailwindcss`, `@fullcalendar/*`, `html5-qrcode`, `qrcode`, `vuedraggable`, `chart.js`, `vue-chartjs`, `sweetalert2`

Missing / optional items (how to complete)
- README and presentation: expanded here.
- Database dump: run `mysqldump` locally and add `database/project_dump.sql` (I can add it if you upload it or run the command and allow me to access the file).
- Tests: No tests included — add `tests/Feature` + `tests/Unit` and run `php artisan test`.
- Real-time dashboards: enable broadcasting (Pusher or Redis) — update `.env` (`BROADCAST_CONNECTION`) and set up Laravel Echo on the frontend.
- Email: configure SMTP in `.env` for production (currently `MAIL_MAILER=log`).
- Audit logs: consider `spatie/laravel-activitylog` for admin audit trails.

Enable Maatwebsite Excel exporter (optional)
1. Install the package:

```bash
composer require maatwebsite/excel
```

2. Publish config (optional):

```bash
php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider"
```

Git / GitHub
1. Initialize repo (if not already):

```bash
git init
git add .
git commit -m "Initial commit: Campus Lab Booking"
```

2. Create a GitHub repository (via website) or use `gh` CLI:

```bash
gh repo create <your-username>/<repo-name> --public --source=. --remote=origin --push
```

3. Or add remote and push manually:

```bash
git remote add origin https://github.com/<your-username>/<repo-name>.git
git branch -M main
git push -u origin main
```

Security & repo notes
- Large DB dumps can bloat the repo. Consider adding `database/project_dump.sql` to `.gitignore` or store dumps in releases/artifacts.
- Remove any sensitive credentials from `.env` before publishing. Use GitHub Secrets for CI.
