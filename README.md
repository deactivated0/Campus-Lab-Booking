## Campus Lab Booking

A Laravel 12 backend with an Inertia + Vue 3 frontend for booking labs and equipment. The app issues time-limited QR tokens for tablet kiosks and records automated usage logs.

---

## Table of contents
- Features
- Repo layout
- Requirements
- Local setup
- Database dump (MySQL)
- Running tests
- Environment & configuration
- Optional/extension tasks
- Third‑party libraries
- Contributing
- License

---

## Features
- User authentication (Laravel auth + Google Socialite)
- Role‑based authorization (Admin, LabStaff, Student) via Spatie permissions
- Lab and equipment booking with calendar UI
- Time‑limited QR tokens for kiosk check‑in
- Automated usage logging from kiosks
- Usage exports (CSV; optional Maatwebsite Excel integration)
- Frontend built with Inertia + Vue 3, Tailwind, FullCalendar, charts, and QR libraries

---

## Repo layout
- routes/
  - web.php
  - auth.php
- app/Http/Controllers/ — backend controllers (BookingController, KioskController, DashboardController, etc.)
- app/Models/ — User, Booking, Equipment, Lab, QRToken, UsageLog
- app/Exports/UsageLogsExport.php
- resources/js/ — Inertia + Vue pages and components
- database/ — migrations, seeders
- tests/ — (not included by default)

---

## Requirements
- PHP >= 8.2
- Composer
- Node.js 16+ and npm
- MySQL (project uses a local project database by default) — Laragon recommended on Windows

---

## Local setup

1. Clone the repo and enter the project directory.

2. Install PHP dependencies:
   ```
   composer install
   ```

3. Install JS dependencies and build:
   ```
   npm ci
   npm run dev
   ```

4. Copy and configure environment:
   ```
   cp .env.example .env
   # Edit .env: set DB_DATABASE, DB_USERNAME, DB_PASSWORD, APP_URL and other values
   php artisan key:generate
   ```

5. Create the database (manually or via your DB tool) and run migrations and seeders:
   ```
   php artisan migrate --force
   php artisan db:seed
   ```

6. Start the Laravel dev server:
   ```
   php artisan serve --host=127.0.0.1 --port=8000
   ```
   Open APP_URL in your browser.

---

## MySQL dump (full: structure, data, triggers, routines, events)

Create a full dump locally (replace credentials as needed).

If mysqldump is in your PATH:
```
mysqldump -u root -p --single-transaction --routines --events --triggers project > database/project_dump.sql
```

Laragon/Windows example (adjust path):
```
& 'C:\laragon\bin\mysql\mysql-8.0.33\bin\mysqldump.exe' -u root -p --single-transaction --routines --events --triggers project > database/project_dump.sql
```

Compress:
```
gzip -c database/project_dump.sql > database/project_dump.sql.gz
```

Note: Run the dump locally and add database/project_dump.sql to the repo if you want it included.

---

## Running tests
No tests are included by default. Recommended workflow:
- Add Feature and Unit tests under tests/
- Run:
  ```
  php artisan test
  ```

---

## Environment & configuration notes
- Broadcasting (real‑time dashboards): set BROADCAST_CONNECTION (pusher or redis) and configure Laravel Echo on the frontend.
- Email: set SMTP in .env for production (default MAIL_MAILER=log).
- Permissions: Spatie roles and permissions are wired into routes via middleware.
- Exports: UsageLogsExport supports Maatwebsite Excel (optional). Fallback CSV export exists.

---

## Optional / Missing items
- README: this file (polished)
- Database dump: add database/project_dump.sql from a local mysqldump
- Tests: add tests/Feature and tests/Unit
- Real‑time updates: enable broadcasting and configure Echo
- Audit logs: consider spatie/laravel-activitylog for admin audit trails
- Install Maatwebsite Excel for richer export formats (see below)

Enable Maatwebsite Excel exporter (optional):
```
composer require maatwebsite/excel
php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" # optional
```

---

## Third‑party libraries (high level)
Composer:
- inertiajs/inertia-laravel
- laravel/socialite
- spatie/laravel-permission
- tightenco/ziggy

NPM:
- vue, @inertiajs/vue3
- tailwindcss
- @fullcalendar/*
- html5-qrcode, qrcode
- vuedraggable
- chart.js, vue-chartjs
- sweetalert2

---

## Contributing
- Fork the repo, create a feature branch, open a PR with a clear description.
- Add tests for new features/bug fixes.
- Keep migrations and seeders in sync.

---

## License
Specify project license (add LICENSE file if needed).

---

If you want, I can:
- Add this README to the repo (commit + PR),
- Generate a CONTRIBUTING.md or CHANGELOG.md,
- Create a basic tests scaffold (Feature examples). Which should I do next?

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

