# Publishing software updates

DiamondCMS updates are GitHub Releases of the **production ZIP** from `php scripts/build-release.php`.

## Maintainers (local)

1. Bump `VERSION` and `CHANGELOG.md`.
2. Commit and push.
3. `php scripts/build-release.php`
4. Create a GitHub release tag `vX.Y.Z`.
5. Attach:
   - `storage/app/releases/diamondcms-X.Y.Z.zip`
   - `storage/app/releases/diamondcms-X.Y.Z.zip.sha256`

## Production (Admin → System)

1. **Check GitHub** — compares running `VERSION` to the latest release.
2. **Download & stage** — pulls the ZIP, verifies SHA-256 when a `.sha256` asset exists.
3. **Apply** — maintenance mode, DB backup, copies code over the app, runs migrations, health check.

### Preserved on apply (never overwritten)

- `.env`
- entire `storage/` tree (media, sessions, logs, backups, site exports)
- `public/storage` symlink
- root `.htaccess` (keeps Bluehost PHP handler)

Site **content** (pages, forms, resumes, etc.) lives in the database and is not replaced by a software update. Use **site package export/import** to move content between environments.
