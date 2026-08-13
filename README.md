# TMS — Tailor Management System

TMS is a multi-tenant business management platform for tailoring workshops and clothing shops. It combines customer measurements, tailoring orders, workshop progress, tailor accounts, cloth inventory, purchasing, sales, suppliers, expenses, reporting, storefront operations, and team permissions in one Urdu-friendly application.

The client interface is designed primarily for right-to-left workflows, with permission-aware tailoring and clothing workspaces. The system also includes responsive dashboards and print layouts optimized for standard A4 documents and POS-80C 80 mm thermal printers.

## Main features

### Tailoring

- Customer and sub-customer records
- Reusable measurement profiles and custom measurement fields
- Tailoring order creation, editing, printing, and delivery tracking
- Assigned → cutting → stitching → trial → ready → delivered workflow
- Tailor selection and database-backed stitching rates
- Workshop board and order status history
- Tailor order history with search and date filtering
- Tailor advances, security deposits, payments, and account reports
- Weekly tailor reports and POS-80C print receipts
- Separate production workers, work types, compensation, and assignments

### Clothing shop

- Cloth brands, types, colors, racks, images, and available stock
- Counter sales and customer-linked sales
- Supplier management and supplier balances
- Draft, received, and cancelled purchases
- Partial and complete purchase receiving
- Purchase returns and payable reversal
- Inventory movement ledger and valuation
- Weighted-average costing plus visibility of the latest purchase rate
- Low-stock indicators and shop reporting

### Platform and security

- Separate tailoring and clothing workspaces
- Multi-tenant business isolation
- Client-defined employee roles and granular permissions
- Super-admin client and subscription management
- Employee activation, temporary passwords, and password policies
- Customer and independent-tailor authentication
- Activity records and in-app confirmation dialogs
- Responsive Urdu client UI and English administrative UI
- Public storefront and customer mobile API support

## Technology

| Component | Version / package |
|---|---|
| PHP | 8.2 or newer |
| Framework | Laravel 12 |
| Database | SQLite by default; MySQL is supported |
| Frontend build | Vite 5 |
| CSS/UI | Bootstrap, Tailwind CSS, Sass |
| Authorization | Spatie Laravel Permission 6 |
| Authentication/API | Laravel Sanctum 4 |
| Realtime | Laravel Reverb / Echo |
| Testing | PHPUnit 11 |

## Requirements

- PHP 8.2+
- Composer 2
- Node.js 18+ and npm
- SQLite or MySQL
- Required PHP extensions for Laravel and the selected database driver

For MySQL, enable `pdo_mysql`. For SQLite, enable `pdo_sqlite` and `sqlite3`.

## Local installation

### 1. Install dependencies

```bash
composer install
npm install
```

### 2. Create the environment file

On PowerShell:

```powershell
Copy-Item .env.example .env
```

On macOS or Linux:

```bash
cp .env.example .env
```

Then generate the application key:

```bash
php artisan key:generate
```

### 3. Configure the database

The example environment uses SQLite. Create the database if it does not exist:

```powershell
New-Item -ItemType File -Path database/database.sqlite -Force
```

Or configure MySQL in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tms
DB_USERNAME=root
DB_PASSWORD=
```

When using PHP 8.5+, the application uses the compatible MySQL SSL attribute automatically. Do not add `/public` to application asset URLs.

### 4. Create the database structure

For an empty development database with sample data:

```bash
php artisan migrate --seed
```

For an existing production database, take a backup first and run:

```bash
php artisan migrate --force
```

> The default seeder creates QA/demo data. Do not run `db:seed` on a production database unless that is intentional.

### 5. Link public storage

```bash
php artisan storage:link
```

This link is required for files saved under `storage/app/public`. Shop logos currently saved under `public/images/setting` are served directly and do not depend on this link.

### 6. Build frontend assets

For development:

```bash
npm run dev
```

For a production build:

```bash
npm run build
```

### 7. Start the application

Run the web server:

```bash
php artisan serve
```

The default local address is:

```text
http://127.0.0.1:8000
```

Because the application uses database-backed queues, start a worker in another terminal when testing queued notifications or background tasks:

```bash
php artisan queue:work
```

Alternatively, run the combined development command:

```bash
composer run dev
```

This starts the Laravel server, queue listener, application logs, and Vite.

## Development seed account

The base development seeder creates a super-administrator account:

```text
Email: admin@gmail.com
Password: admin@1234
```

These credentials are for local development only. Change or remove them before deploying the application.

## Environment notes

Recommended local values:

```dotenv
APP_NAME="TMS"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Karachi

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Production must use:

```dotenv
APP_ENV=production
APP_DEBUG=false
```

After changing environment or configuration values, clear cached state:

```bash
php artisan optimize:clear
```

## Uploaded images and logos

Images inside Laravel's `public` directory are already publicly accessible. For example:

```text
File: public/images/setting/logo.png
URL:  /images/setting/logo.png
```

Correct Blade usage:

```blade
@if($setting?->logo_url)
    <img src="{{ $setting->logo_url }}" alt="{{ $setting->name }} لوگو">
@endif
```

Do not use `asset('public/images/...')`; that produces an invalid `/public/images/...` request.

## POS-80C thermal printing

The order receipt and tailor weekly report include layouts for 80 mm thermal paper.

Recommended printer-dialog settings:

- Printer: POS-80C
- Paper width: 80 mm
- Scale: 100%
- Margins: None
- Headers and footers: Disabled
- Background graphics: Enable only when required by the selected receipt

Shop print preferences are available in the application settings. The supported paper profiles are:

- 80 mm receipt
- A4 full page

## Useful commands

```bash
# Clear all application caches
php artisan optimize:clear

# Rebuild cached Blade templates
php artisan view:clear
php artisan view:cache

# Run the queue worker
php artisan queue:work

# Build production assets
npm run build

# Run the automated test suite
php artisan test

# Run one test class or method
php artisan test --filter=ClientModuleAccessTest

# Check PHP style
./vendor/bin/pint --test
```

On Windows, Pint can be run with:

```powershell
vendor\bin\pint.bat --test
```

## Testing

The feature test suite covers key application boundaries, including:

- Module and workspace access
- Employee business permissions
- Tenant isolation
- Tailoring workflows and reports
- Inventory movements and costing
- Purchase receiving and returns
- Customer authentication and balances
- Subscription entitlements
- In-app destructive-action confirmations

Use a dedicated test database. The default `phpunit.xml` configuration should never point to production data.

## Project structure

```text
app/                 Controllers, models, services, middleware, and policies
database/migrations  Database schema and incremental changes
database/seeders     Base and QA/demo seed data
docs/                Implementation notes and QA documentation
public/              Web entry point and directly served assets
resources/views/     Blade templates and print layouts
resources/js/        Frontend JavaScript entry points
resources/css/       Frontend styles
routes/              Web, API, and application routes
storage/             Logs, cache, compiled views, and stored files
tests/Feature/       End-to-end application feature tests
```

## Production checklist

Before deployment:

1. Back up the database and uploaded files.
2. Configure production database, mail, queue, cache, and broadcast values.
3. Set `APP_DEBUG=false` and a correct HTTPS `APP_URL`.
4. Replace all development/demo credentials.
5. Run `composer install --no-dev --optimize-autoloader`.
6. Run `npm ci && npm run build`.
7. Run `php artisan migrate --force`.
8. Run `php artisan storage:link` when stored public files are used.
9. Run `php artisan optimize`.
10. Configure a persistent queue worker and scheduled task runner.
11. Point the web server document root to the project's `public` directory.

Laravel's scheduler should run every minute:

```cron
* * * * * cd /path/to/TMS && php artisan schedule:run >> /dev/null 2>&1
```

## Security

- Never commit `.env`, database backups, API keys, or production credentials.
- Always scope business data through the authenticated business owner or tenant.
- Preserve permission middleware when adding administrative routes.
- Validate uploaded files and financial inputs on the server.
- Hash passwords and never render them in views or logs.
- Use HTTPS and secure cookies in production.
- Review demo seeders before running them outside local or QA environments.

## Additional documentation

More detailed implementation and QA notes are available in the [`docs`](docs) directory, including:

- [`FEATURE_INVENTORY_AND_QA_MATRIX.md`](docs/FEATURE_INVENTORY_AND_QA_MATRIX.md)
- [`IMPLEMENTATION_STATUS.md`](docs/IMPLEMENTATION_STATUS.md)
- [`FULL_QA_REPORT_2026-07-23.md`](docs/FULL_QA_REPORT_2026-07-23.md)

## License

No project-specific license file is currently included. Treat this repository as private/internal software unless the owner adds an explicit license.
