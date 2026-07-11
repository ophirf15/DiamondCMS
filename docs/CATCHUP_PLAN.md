# DiamondCMS Catch-Up Plan (Reality vs Phases)

**Date:** 2026-07-11  
**Verdict:** Phases 0–12 are largely **API/domain complete**. The admin UI is a thin SPA. This is not WordPress/Joomla competitive yet.

## What you can click today

- Installer, login, page builder, publish/preview
- Media: upload + count only
- Templates: names + **gradient fakes** (same underlying starter JSON)
- Resumes: create name only
- No Settings / Theme / Menus / Forms / SMTP / Account / Portfolio / AI / Backups screens

## Brand kit

Applied from `Diamond Brand logo/` → `public/brand/` (+ favicons). Product chrome (admin, login, installer) uses Diamond Builder marks. Site owners will override via Theme settings later.

## Wave A — Dogfood MVP (do this first)

1. **Settings hub** — General (site name, homepage), SMTP + test email, permalinks explanation (`/{slug}`)
2. **Theme / branding** — logo, colors, fonts, light/dark → existing `DesignManager` tokens; live preview
3. **Real templates** — distinct layouts per starter; **live mini-preview** (not gradients); Preview | Use
4. **Media library** — grid + builder image picker
5. **Menus** — admin editor; wire public header (stop hardcoding Projects/Admin)
6. **Forms** — builder + submissions; SSR form blocks; SMTP end-to-end

**Success bar:** Install → set logo/SMTP → pick a template you can *see* → edit home → publish → receive contact mail — no JSON, no docs required.

## Wave B — Production personal site

Account (password, 2FA, admins), full Résumé UI, Portfolio CRUD, SEO fields + redirects, page revisions/duplicate/schedule/password, activity log.

## Wave C — Platform

AI UX with approval, backups/export/updater UI, Elementor-depth builder, Playwright e2e. Phases 13–14 only after Wave B runs ophiryahalom.com.

## Rules

- UI before claiming a phase done again
- Site **theme** ≠ page **templates**
- Prefer vertical slices that touch public site, not API-only PRs

## Immediate build order

Brand assets → Settings+SMTP → Theme → Templates+previews → Media+picker → Menus → Forms.
