# DiamondCMS Developer Guide

## Architecture
DiamondCMS is a Laravel modular monolith. Domain logic lives under `app/Domains/*/Support`, public pages render with Blade, and admin operations use authenticated routes under `routes/admin.php`.

## Builder Schema
Builder JSON is versioned by `App\Domains\Builder\Support\BuilderDocument::CURRENT_SCHEMA`. Add new blocks through the registry, validation, and renderer together.

## Testing
Use `php artisan test`. The Phase 7-12 regression coverage is in `tests/Feature/PhaseSevenToTwelveTest.php`.

## Release Build
Run `php scripts/build-release.php`. The build excludes `.env`, `node_modules`, local uploads, and tests, and writes checksums beside the ZIP.
