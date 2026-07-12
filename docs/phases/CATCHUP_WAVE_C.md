# Catch-up Wave C — Platform (partial)

**Status:** 2026-07-11 — **partial ship**, not claiming Elementor-complete

## Shipped in this catch-up

| Area | Status |
|------|--------|
| **AI UX** | Admin **AI** panel: save provider (encrypted key), generate draft page, approve → draft page. No secrets committed. |
| **Backup / updater** | Admin **System** panel: create backup, export ZIP (path on server, secrets excluded), restore, stage update with checksum verify |
| **Builder embeds** | Section nesting shortcuts for columns / form / résumé / portfolio; clearer embed chrome; fixed `resume-download` SSR link |
| **Playwright** | **Not shipped** — would need `@playwright/test` + browser install; Feature/Pest coverage used instead |

## APIs used (existing)

- `POST /admin/api/ai/providers`, `generate-draft-page`, `generations/{id}/approve`
- `GET/POST /admin/api/backups`, `POST /exports`, `POST /backups/{id}/restore`
- `POST /admin/api/updates/stage`

## Still deferred

- Live canvas preview of form/resume SSR (still inspector + publish-time render)
- Deeper Elementor nesting (containers beyond section/columns)
- Downloadable export via HTTP (ZIP written to server path only)
- Update activation / rollback orchestration beyond staging
- Playwright smoke suite

Phases 13–14 (production cutover for ophiryahalom.com) remain after dogfooding Waves A–B on a real site.
