# Changelog

All notable changes to DiamondCMS are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.1] - 2026-07-12

### Fixed
- GitHub update check uses `ophirf15/DiamondCMS`, returns a clear message when no Release exists, and supports private-repo asset downloads.
- Admin API toasts show a short error message instead of Laravel debug exception dumps.

### Changed
- Tag-triggered GitHub Actions workflow publishes production ZIP releases.

## [0.1.0] - 2026-07-11

### Added
- Phase 0 foundation: Laravel 13 modular monolith, Vue 3 + TypeScript admin, health endpoints, release build script, ADR, CI
- Phase 1 web installer, admin auth, 2FA, throttling, activity log
- Phase 2 admin dashboard, pages, revisions, menus, scheduling, preview
- Phase 3 visual page builder with VueDraggable Plus, schema validation, SSR renderers
- Phase 4 design tokens, branding, starter templates
- Phase 5 media library with variants, chunked upload, builder picker
- Phase 6 résumé system with import, variants, PDF export
- Phase 7 portfolio, projects, personal content collections
- Phase 8 forms, encrypted SMTP, spam controls, submissions
- Phase 9 AI provider layer with draft-only generation and approval
- Phase 10 SEO, sitemap, accessibility, analytics consent
- Phase 11 backups, export/import, GitHub updater with rollback
- Phase 12 security hardening, Playwright coverage, checksummed RC

### Changed
- Replaced the scaffold README with DiamondCMS setup, verification, release, and deployment guidance.
- Added a TypeScript compiler config and `npm run typecheck` for the admin frontend.
- Kept `composer.lock` in release bundles for reproducible production ZIP installs.

### Security
- Explicitly ignored `storage/app/installed.lock` so installer state is never committed.

[0.1.1]: https://github.com/ophirf15/DiamondCMS/releases/tag/v0.1.1
[0.1.0]: https://github.com/ophirf15/DiamondCMS/releases/tag/v0.1.0
