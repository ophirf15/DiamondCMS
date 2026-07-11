# Phase 11 Completion — Backups, Export/Import, Updater

## Implemented
- JSON database backup records with checksums and manifests.
- Full-site export ZIP path excluding `.env`, SMTP passwords, API keys, and local secrets.
- Import dry-run reporting with merge/replace mode support, pre-import backup recording, apply flow, and restore-on-failure rollback.
- Update staging with SHA-256 checksum verification.
- Admin routes for backups, restore, exports, import dry runs, import apply, and update staging.

## Verification
- Covered by `tests/Feature/PhaseSevenToTwelveTest.php`.
- Failed update activation is documented as rollback-to-backup; activation is intentionally staged behind checksum verification.
