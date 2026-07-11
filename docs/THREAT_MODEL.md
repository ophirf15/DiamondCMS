# Threat Model Notes

## Protected Assets
- Admin accounts and sessions.
- SMTP passwords and AI provider keys.
- Form submissions and uploaded files.
- Builder JSON and custom HTML.
- Backup/export packages and update ZIPs.

## Controls
- Admin routes require authentication and `is_admin`.
- CSRF protection applies to web/admin form submissions.
- SMTP and AI secrets are encrypted server-side.
- AI context excludes secrets and form submissions by default.
- Builder HTML strips event handlers and style attributes.
- Sitemap excludes drafts and password-protected pages.
- Release/update ZIPs require SHA-256 verification before staging.
- Security headers and CSP are applied to web responses.

## Residual Risks
- Full visual admin screens are intentionally minimal in this implementation pass.
- Update activation is staged and logged; production activation should be tested on a copy before live use.
