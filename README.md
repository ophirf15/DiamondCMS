# DiamondCMS

DiamondCMS is a Laravel 13 modular monolith for a personal CMS, admin dashboard, visual page builder, media library, and resume system. The admin UI is Vue 3 + TypeScript compiled through Vite; production releases include compiled assets and `vendor`, so Node.js is not required on the target host.

## Local Setup

Requirements:

- PHP 8.3 with `pdo_mysql`, `mbstring`, `openssl`, `gd`, `zip`, `intl`, and `fileinfo`
- MariaDB/MySQL
- Composer
- Node.js LTS for local asset builds

For the local Windows database described in the phase plan, use database `diamondcms`, user `diamondcms`, and password `diamondcms_local`. Then run:

```powershell
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve
```

Open `/install` for the browser installer wizard. After install, the admin area is available at `/admin/dashboard`; health checks are available at `/health` and `/admin/health`.

## Development

Useful checks:

```powershell
php artisan test
npm run typecheck
npm run build
```

Build a production ZIP with:

```powershell
php scripts/build-release.php
```

The generated ZIP excludes runtime state, secrets, plans, tests, and `node_modules`, and includes compiled assets plus Composer dependencies.

## Deployment

Point Apache or LiteSpeed to the `public` directory. See `docs/LOCAL_INSTALL.md` and `docs/APACHE_LITESPEED.md` for the Windows database setup, shared hosting notes, cron/web scheduler fallback, and installer lock behavior.
