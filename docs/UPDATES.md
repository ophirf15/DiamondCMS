# Publishing software updates

DiamondCMS updates are GitHub Releases of the **production ZIP** from `php scripts/build-release.php`.

Repo: `ophirf15/DiamondCMS` (set `DIAMONDCMS_GITHUB_REPO` if different).

## Maintainers (local / CI)

A normal push to `master` runs **CI only**. It does **not** create a GitHub Release.

Preferred (automatic Release):

```bash
# bump VERSION + CHANGELOG, commit + push to master, then:
git tag v0.1.1
git push origin v0.1.1
```

That triggers `.github/workflows/release.yml`, which builds the production ZIP and publishes a
Release with `diamondcms-*.zip` + `.sha256` attached. The in-app updater reads **Releases**, not tags alone.

Manual fallback:

1. Bump `VERSION` and `CHANGELOG.md`.
2. Commit and push.
3. `php scripts/build-release.php`
4. Create a GitHub release tag `vX.Y.Z`.
5. Attach:
   - `storage/app/releases/diamondcms-X.Y.Z.zip`
   - `storage/app/releases/diamondcms-X.Y.Z.zip.sha256`

## Production (Admin → System)

On Bluehost `.env`:

```env
DIAMONDCMS_GITHUB_REPO=ophirf15/DiamondCMS
DIAMONDCMS_GITHUB_TOKEN=ghp_...   # required while the repo is private
```

Token needs at least read access to repository contents / releases.

1. **Check GitHub** — compares running `VERSION` to the latest **published** Release.
2. **Download & stage** — pulls the ZIP, verifies SHA-256 when a `.sha256` asset exists.
3. **Apply** — maintenance mode, DB backup, copies code over the app, runs migrations, health check.

### Preserved on apply (never overwritten)

- `.env`
- entire `storage/` tree (media, sessions, logs, backups, site exports)
- `public/storage` symlink
- root `.htaccess` (keeps Bluehost PHP handler)

Site **content** (pages, forms, resumes, etc.) lives in the database and is not replaced by a software update. Use **site package export/import** to move content between environments.
