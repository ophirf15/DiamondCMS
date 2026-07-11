# Phase 12 Completion — Hardening, Testing, RC

## Implemented
- Global security headers and CSP middleware.
- Threat model, admin, developer, troubleshooting, shared-host, and deployment handoff documentation.
- Focused PHPUnit coverage for critical Phase 7–12 gates (18 tests / 44 assertions passing).
- Checksummed RC ZIP under `storage/app/releases/`.

## Release candidate
- File: `storage/app/releases/diamondcms-0.1.0.zip`
- SHA-256: `e5e5d8a4f759ce4bb133a0440e5a60ce8c3850efbacdba6bf6ef34c180181592`
- Manifest: `storage/app/releases/diamondcms-0.1.0/release-manifest.json`
- ZIP excludes `.env`, `node_modules`, and `tests`; includes `vendor` and compiled `public/build`.

## Verification
- `php artisan test` — passed
- `npm run build` — passed
- `php scripts/build-release.php` — produced ZIP + `.sha256`
- Smoke: `/health` OK; installer at `/install`; admin at `/admin`

## Handoff
See `docs/START_TESTING_HERE.md`.
