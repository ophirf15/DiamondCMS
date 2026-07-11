# Start Testing Here

DiamondCMS **0.1.0** release candidate is ready for manual testing.

## Quick local run (this machine)

Toolchain is already installed (PHP 8.3, Composer, MariaDB 12.3, Node 24, Git).

1. Ensure MariaDB is running on port `3306` (if needed):
   ```powershell
   & "C:\Program Files\MariaDB 12.3\bin\mariadbd.exe" --datadir="C:\Program Files\MariaDB 12.3\data" --port=3306
   ```
2. From `c:\Users\ophir\DiamondCMS`:
   ```powershell
   php artisan serve
   ```
3. Open **http://127.0.0.1:8000**

### Database (already created for local dev)

| Setting | Value |
|---------|-------|
| Host | `127.0.0.1` |
| Port | `3306` |
| Database | `diamondcms` |
| Username | `diamondcms` |
| Password | `diamondcms_local` |

These match the current `.env`.

## Fresh install path (recommended first test)

1. Delete `storage/app/installed.lock` if present (or use installer recovery with `DIAMONDCMS_RECOVERY_KEY`).
2. Visit **http://127.0.0.1:8000/install**
3. Complete requirements → database → site name / first admin
4. Sign in at **http://127.0.0.1:8000/login**
5. Open admin at **http://127.0.0.1:8000/admin/dashboard**

## What to exercise

1. Installer → login → dashboard
2. Create a page in the visual builder → publish → view public URL
3. Enable 2FA from admin API / settings flow
4. Upload media; pick image in builder
5. Résumé import (PDF/DOCX) → review → approve → public/print
6. Portfolio project → `/projects`
7. Contact form + SMTP test (you supply SMTP)
8. AI provider keys (you supply) → draft generation → approve (never auto-publish)
9. Backup / export / import dry-run
10. Confirm `/health`, `/sitemap.xml`, `/robots.txt`

## Release candidate

- ZIP: `storage/app/releases/diamondcms-0.1.0.zip` (~8.3 MB)
- SHA-256: `e5e5d8a4f759ce4bb133a0440e5a60ce8c3850efbacdba6bf6ef34c180181592`
- Checksum file: `storage/app/releases/diamondcms-0.1.0.zip.sha256`
- Rebuild: `php scripts/build-release.php` (then `composer install` to restore local dev deps)

Production hosts need **PHP 8.3+ and MySQL/MariaDB only** (no Node).

## Docs map

- Admin: `docs/ADMIN_MANUAL.md`
- Developer: `docs/DEVELOPER_GUIDE.md`
- Troubleshooting: `docs/TROUBLESHOOTING.md`
- Deploy checklist: `docs/OPHIRYAHALOM_DEPLOY_CHECKLIST.md`
- Phase reports: `docs/phases/PHASE_00_COMPLETION.md` … `PHASE_12_COMPLETION.md`
