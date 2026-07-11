# Phase 4 — Design System, Branding, and Templates

## Objective

Create the global style system and the initial complete template library.

## Cursor prompt

```text
You are implementing Phase 4: Design System, Branding, and Templates of DiamondCMS.

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
- Build global color palette and design tokens.
- Add gradient builder.
- Add typography and font-pair controls.
- Add Google Fonts integration.
- Add local font upload with license-warning metadata.
- Add logo, alternate logo, and favicon.
- Add buttons, borders, radii, shadows, spacing scale, containers, links, forms, and navigation styles.
- Add light, dark, and automatic modes.
- Add animation presets and reduced-motion behavior.
- Add section backgrounds, images, and video.
- Add global CSS variable generation.
- Add advanced custom CSS with revision and rollback.
- Add advanced custom JavaScript with warnings, permission checks, and safe placement controls.
- Build approximately ten complete starter-site templates:
  1. Dark technical résumé
  2. Minimal professional résumé
  3. Creative portfolio
  4. Property-management professional
  5. Developer and technical-project portfolio
  6. Photography or visual-art portfolio
  7. Personal biography and interests
  8. Split-screen résumé
  9. Editorial case-study portfolio
  10. Modern one-page personal site
- Build reusable page, section, header, footer, contact, project, resume, and 404 templates.
- Ensure templates are fully editable in the builder.

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

- Global branding changes propagate consistently.
- Template installation creates editable builder content.
- All ten starter templates are responsive.
- Templates pass basic accessibility checks.
- Dark technical résumé provides a modern interpretation of the supplied screenshot without copying it exactly.
- Custom CSS and JavaScript changes are revisioned and reversible.
