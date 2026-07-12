# Shared hosting / Bluehost

Preferred layout: extract the full app under `public_html` (or a subdomain folder).
Keep the root `.htaccess` (rewrites into `/public` + PHP handler) and `public/.htaccess`.

After upload:

1. MultiPHP Manager → PHP 8.3 or 8.5 → Apply (rewrites the handler block if needed)
2. Create `.env` from `.env.example` with real `DB_*` credentials
3. Set `APP_DEBUG=true` until install works, then set `false`
4. Visit `/install`

If you see Laravel's blank **500 | Server Error**, set `APP_DEBUG=true` in `.env` to reveal the real exception.

Notes:

- Point the document root at `/public` when the host supports it; otherwise use the root `.htaccess` fallback.
- Keep queue and mail synchronous unless cron is configured.
- Ensure `storage/` and `bootstrap/cache/` are writable (release ZIP includes scaffold dirs).
- Do not upload a local `.env` with secrets; use host MySQL credentials.
- After import, media files live in `storage/app/public`. They are served at `/storage/...`.
- If thumbnails are blank: create the link with `php artisan storage:link`, or rely on the
  built-in `/storage/{path}` route (no symlink required). Confirm a file exists at
  `storage/app/public/media/...` and open `/storage/media/...` in the browser.
