# Phase 10 — Public Site, SEO, Accessibility, and Performance

## Objective

Complete the public-facing platform, search optimization, analytics, accessibility, and performance controls.

## Cursor prompt

```text
You are implementing Phase 10: Public Site, SEO, Accessibility, and Performance of DiamondCMS.

Read these files first:
- 01_MASTER_PRODUCT_SPECIFICATION.md
- 02_ARCHITECTURE_AND_GUARDRAILS.md
- All completed prior phase documents and completion notes

Before changing code:
1. Inspect the repository.
2. Summarize the relevant existing implementation.
3. Identify dependencies, migrations, security risks, and shared-host constraints.
4. Present a concise implementation plan.
5. Do not begin later phases.

Implementation requirements:
- Add per-page SEO title, description, canonical URL, Open Graph, and social image.
- Add XML sitemap.
- Add robots.txt management.
- Add redirect manager.
- Add custom 404.
- Add sticky and transparent navigation.
- Add dropdown and mobile menus.
- Add cookie-consent banner.
- Add Google Analytics and Tag Manager settings.
- Add controlled custom analytics scripts.
- Add accessibility audit tools.
- Add missing-alt, heading-order, contrast, form-label, link-name, and keyboard checks.
- Add performance auditing and recommendations.
- Add lazy loading.
- Add cache headers and application cache strategy.
- Add CDN-compatible asset URL configuration.
- Add image optimization integration.
- Add secure preview and password-protected behavior to SEO rules.
- Add multilingual-ready route and content abstractions, but no full translation UI is required.
- Add print styles for résumé pages.
- Ensure semantic HTML and reduced-motion support.

Engineering requirements:
- Use production-quality code, not mock screens.
- Preserve shared-host compatibility.
- No Node.js runtime dependency in production.
- Add database migrations and reversible rollback behavior.
- Add authorization and validation.
- Add automated tests.
- Update developer and administrator documentation.
- Update the changelog and version metadata where appropriate.
- Do not commit secrets.
- Do not expose provider keys or server credentials to client-side code.
- Keep existing features working.

Completion response:
1. Summary of implementation
2. Files changed
3. Database migrations
4. Tests added
5. Commands run and results
6. Manual verification checklist
7. Known limitations
8. Recommended next phase
9. Stop and wait for approval
```

## Acceptance criteria

- SEO metadata renders correctly.
- Sitemap excludes private and draft pages.
- Redirects work without loops.
- Accessibility audits identify common issues.
- Public templates meet reasonable performance targets on representative pages.
- Cookie and analytics behavior follows administrator configuration.
- Mobile navigation is fully usable.
