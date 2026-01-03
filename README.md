## Campus Lab Booking

This project is a simple system for booking labs and equipment. It includes a Laravel backend (API + web) and a modern frontend built with Inertia + Vue 3. Students can request bookings, lab staff can approve them, and the system can generate short-lived QR tokens used by tablet kiosks to check users in and record automated usage logs.

If you landed here and want to run the app locally, the Quick Start below walks you through the minimum steps.

---

## Quick Start (for newcomers)

1. Clone the repository and change into the project folder:
   ```bash
   git clone https://github.com/deactivated0/Campus-Lab-Booking.git
   cd Campus-Lab-Booking
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Copy the example environment file and set your database and app URL:
   ```bash
   cp .env.example .env
   # Edit .env and set DB_DATABASE, DB_USERNAME, DB_PASSWORD, APP_URL, etc.
   php artisan key:generate
   ```

4. Install JavaScript dependencies and start the dev frontend (or build for production):
   ```bash
   npm ci
   npm run dev    # development (live reload)
   # or
   npm run build  # production build
   ```

5. Create the database and run migrations + seeders:
   ```bash
   # Ensure your DB exists and credentials in .env are correct
   php artisan migrate --force
   php artisan db:seed
   ```

6. Start the Laravel server and open the app in your browser:
   ```bash
   php artisan serve --host=127.0.0.1 --port=8000
   # Visit: http://127.0.0.1:8000
   ```

Notes for Windows users: Laragon is a convenient local stack for running PHP + MySQL on Windows. If you prefer Laragon, create a project database in the Laragon MySQL panel and update `.env` accordingly.

---

## What this repo contains (short)

- `app/` — Laravel backend code (controllers, models, providers)
- `resources/js/` — Inertia + Vue pages and components (frontend)
- `routes/` — Route definitions
- `database/` — migrations and seeders
- `public/` — compiled frontend assets and entry point

---

## Running in development

- Backend: `php artisan serve`
- Frontend (dev): `npm run dev` (starts Vite with live reload)
- To rebuild production assets: `npm run build`

If you use Docker, put the usual services up (PHP, MySQL) and point the `.env` values at them.

---

## Database export / import (MySQL)

Create a full dump locally (example):

```bash
mysqldump -u root -p --single-transaction --routines --events --triggers project > database/project_dump.sql
gzip -c database/project_dump.sql > database/project_dump.sql.gz
```

Windows (Laragon) example:

```powershell
& 'C:\\laragon\\bin\\mysql\\mysql-8.0.33\\bin\\mysqldump.exe' -u root -p --single-transaction --routines --events --triggers project > database/project_dump.sql
```

---

## Tests

There are no tests by default. To run tests (once you add them):

```bash
php artisan test
```

---

## Configuration notes

- Permissions/roles are handled by `spatie/laravel-permission` (Admin, LabStaff, Student).
- QR tokens: short-lived tokens are created for kiosks and expire automatically.
- Email and broadcasting settings rely on `.env` configuration.

---

## Contributing

If you want to contribute, fork the repo, create a branch, and open a pull request with a clear description of your change. Adding tests for new features is encouraged.

---

## License

Add a `LICENSE` file to choose a license for this project.


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

