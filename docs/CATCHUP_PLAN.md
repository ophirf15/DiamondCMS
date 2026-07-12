# DiamondCMS Catch-Up Plan (Reality vs Phases)

**Date:** 2026-07-11  
**Verdict:** Phases 0–12 are largely **API/domain complete**. Waves A and B admin UI are dogfoodable. Wave C has AI + System panels and builder embed fixes; not Elementor-depth complete.

## Wave A — shipped

See [phases/CATCHUP_WAVE_A.md](./phases/CATCHUP_WAVE_A.md).

Settings, Theme, Templates (live mini-previews), Media, Menus, Forms — install → SMTP → template → publish → contact mail without JSON.

## Wave B — shipped (dogfoodable)

See [phases/CATCHUP_WAVE_B.md](./phases/CATCHUP_WAVE_B.md).

- Account / 2FA (QR + recovery codes) / admins  
- Résumé sections + **public variants** + share/export  
- Portfolio CRUD + public `/projects`  
- SEO meta, audit, redirects, revision restore, activity  

**Tests:** `CatchupWaveBTest` (6) + existing Wave A / phase suites.

## Wave C — partial

See [phases/CATCHUP_WAVE_C.md](./phases/CATCHUP_WAVE_C.md).

AI approval UX and Backup/System UI are clickable. Builder nesting shortcuts + fixed résumé download embed. Playwright deferred (no dep yet).

## Brand kit

`public/brand/` — product chrome uses Diamond marks; Theme overrides for site owners.

## Rules

- UI before claiming a phase done again  
- Site **theme** ≠ page **templates**  
- Prefer vertical slices that touch public site, not API-only PRs  
- No secrets in git  

## Immediate next (production cutover)

Dogfood Waves A–B on ophiryahalom.com content, then Phases 13–14. Optionally add Playwright smoke after `@playwright/test` is adopted.
