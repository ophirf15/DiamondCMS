# Shared hosting / Bluehost
#
# Preferred: point the domain document root at /public
# Alternate: extract the full app into public_html and keep the root
# index.php + .htaccess (they forward into /public and block sensitive paths).
#
# Checklist when every URL returns Apache 500:
# 1. cPanel → MultiPHP / Select PHP Version → PHP 8.3 (required)
# 2. Enable extensions: pdo_mysql, mbstring, openssl, gd, zip, intl, fileinfo
# 3. Document root = .../public  OR use root index.php/.htaccess fallback
# 4. storage/ and bootstrap/cache/ writable (755 dirs, 644 files)
# 5. If still 500: rename public/.htaccess to .htaccess.bak and retest
#    (Options/rewrite directives are a common Bluehost 500 cause)
# 6. Read cPanel → Metrics → Errors for the real PHP/Apache line
#
# Never place .env above a publicly browsable directory without blocking it.

- Point the document root at `/public` when the host supports it.
- Keep queue and mail behavior synchronous unless cron is configured.
- Run imports and exports in dry-run mode first; large packages should be split by media size if memory is constrained.
- Build assets locally before uploading a release ZIP.
- Ensure `storage/` and `bootstrap/cache/` are writable.
- Do not upload `.env` in release packages; configure production secrets on the host.
