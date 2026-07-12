# Premium starters, résumé import, and motion

Shipped 2026-07-12.

## What landed
- New builder blocks: `stats-row`, `social-links`, `timeline`, `gallery-grid` (+ hero kits in admin)
- Public `sidebar-dark` shell for Ophir-style sites
- 10 distinct starter templates with `preview_theme` + themed Templates admin previews
- Public motion via `resources/js/public.ts` + `data-dc-animate`; respects `motion.enabled`
- Résumé import UI: PDF (smalot/pdfparser) / DOCX / TXT → review → approve

## Dogfood
1. Admin → Templates → Refresh starter set
2. Confirm previews look different (Ophir sidebar vs navy AI vs light tech vs split vs neon vs gallery)
3. Use **Ophir professional** → Theme should flip dark/teal; header menu seeds if empty
4. Resumes → Import resume (TXT/PDF/DOCX) → edit review → Approve
5. Publish page + scroll public site for reveal animations
