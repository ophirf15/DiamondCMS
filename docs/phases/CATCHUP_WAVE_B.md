# Catch-up Wave B — Dogfoodable admin slices

**Shipped:** 2026-07-11

## What you can click now

| Area | Admin UI | Public / SSR |
|------|----------|--------------|
| **Account** | Profile + password, 2FA with **QR SVG** + recovery codes, admin create/disable | Login challenge accepts TOTP **or** one-time recovery code |
| **Resumes** | Profile, typed sections, **public variants**, share link, HTML export/print | `/resume/{slug}`, share token, print download; builder download block links to public print URL |
| **Portfolio** | Categories, create/edit/delete, featured + publish toggles | `/projects`, `/projects/{slug}` with case study |
| **SEO** | Meta fields, **audit**, redirects add/delete, revision restore, activity feed | Redirects honored on `/{slug}`; sitemap includes published projects |

## Dogfood path

1. Account → enable 2FA → scan QR → confirm → save recovery codes  
2. Resumes → create profile + sections → **Publish public variant** → open `/resume/{slug}`  
3. Portfolio → add category → create project → view `/projects`  
4. SEO → set meta → Run audit → add redirect → Restore a revision  

## Key files

- Panels: `AccountPanel`, `ResumesPanel`, `PortfolioPanel`, `SeoPanel`
- APIs: `routes/admin.php` (`/admin/api/...`)
- Tests: `tests/Feature/CatchupWaveBTest.php` (6 passing)

## Honest gaps

- Resume “PDF” is browser-print HTML (`X-DiamondCMS-PDF-Mode`), not a binary PDF engine  
- Resume import UI still not in the panel (API exists)  
- Portfolio gallery/metrics/builder_json on detail remain thin  
- Playwright e2e not added (no Playwright dep in package.json yet)
