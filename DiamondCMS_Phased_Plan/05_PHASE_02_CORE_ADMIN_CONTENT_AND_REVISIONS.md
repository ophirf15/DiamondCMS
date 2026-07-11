# Phase 2 — Core Admin, Pages, Content, and Revisions

## Objective

Build the main admin backend, page management, publishing workflow, settings, revisions, menus, and scheduling.

## Cursor prompt

```text
You are implementing Phase 2: Core Admin, Pages, Content, and Revisions of DiamondCMS.

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
- Build the authenticated admin dashboard.
- Add site settings.
- Add page CRUD.
- Add slugs and URL validation.
- Add draft, scheduled, published, and archived states.
- Add revision history and rollback.
- Add secure preview links.
- Add page duplication.
- Add custom 404 page selection.
- Add password-protected pages.
- Add navigation menus with nested items.
- Add global header and footer records.
- Add scheduled-publishing support with cron and web-safe fallback diagnostics.
- Add activity history for content changes.
- Add autosave API foundations.
- Create multilingual-ready page schema without requiring translation UI yet.
- Add admin search and pagination.
- Add dashboard status cards and setup checklist.

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

- Administrators can create, edit, preview, publish, schedule, archive, duplicate, and restore pages.
- Menu nesting works.
- Revisions are immutable and restorable.
- Protected pages require correct credentials.
- Scheduled publishing works through documented host-compatible scheduling.
- Authorization tests cover all content actions.
