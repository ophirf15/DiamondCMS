# Catch-up Wave A — Dogfood MVP

**Shipped:** 2026-07-11

## What you can click now

| Area | Admin UI | Public / SSR |
|------|----------|--------------|
| **Settings** | General (site name, homepage slug), SMTP + test email, permalinks explanation | `diamondcms_site_name()` in header/title; homepage slug setting honored at `/` |
| **Theme** | Logo (brand kit defaults), light/dark/auto, colors, fonts + live preview → `DesignManager` | CSS variables + logo URL from tokens |
| **Templates** | Distinct starter JSON per template; live mini-preview via `BuilderBlockView`; Preview \| Use | N/A |
| **Media** | Grid, upload, delete, copy URL; builder image picker | `/storage/...` URLs |
| **Menus** | Header/footer editor (page or custom URL) | Public nav from DB; Admin link **only when logged in** |
| **Forms** | List / field builder / submissions / ensure contact; notification recipients | SSR form embeds on pages; SMTP notifications on submit |

## Brand kit

Defaults use `public/brand/` (gold logo, favicons). Theme panel can override.

## Dogfood path

1. Settings → set site name + SMTP → send test  
2. Theme → pick logo / colors → Save  
3. Templates → Preview a real layout → Use → edit → Publish (slug `home`)  
4. Forms → Ensure contact → set notification email  
5. Menus → wire header links  
6. Submit contact form → receive mail  

## Key files

- Admin panels: `resources/js/components/admin/*Panel.vue`
- Shell: `resources/js/components/AdminApp.vue`
- Starters: `app/Domains/Builder/Support/StarterTemplates.php`
- Menus: `app/Domains/Design/Support/MenuManager.php`
- Forms SSR + notify: `FormManager`, `public/forms/embed.blade.php`
- Tests: `tests/Feature/CatchupWaveATest.php` (6 passing)

## Wave B / C

Wave B is dogfoodable — see [CATCHUP_WAVE_B.md](./CATCHUP_WAVE_B.md). Wave C is partial — see [CATCHUP_WAVE_C.md](./CATCHUP_WAVE_C.md).

## Not claiming “WordPress-complete”

Elementor-depth builder and Playwright e2e remain open items under Wave C.
