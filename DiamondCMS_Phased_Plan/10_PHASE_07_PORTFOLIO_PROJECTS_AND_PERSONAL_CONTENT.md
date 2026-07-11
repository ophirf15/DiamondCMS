# Phase 7 — Portfolio, Projects, Hobbies, and Personal Content

## Objective

Implement rich structured portfolio records and reusable personal-content blocks.

## Cursor prompt

```text
You are implementing Phase 7: Portfolio, Projects, Hobbies, and Personal Content of DiamondCMS.

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
- Create structured records for projects, hobbies, interests, testimonials, galleries, documents, and timeline entries.
- Add all project fields from the master specification.
- Add categories, tags, skills, dates, status, featured state, and visibility.
- Add project filtering by category, skill, year, type, status, and featured.
- Add case-study editor.
- Add related projects.
- Add before-and-after media.
- Add gallery and video support.
- Add project grids, cards, detail views, timelines, hobby cards, interest lists, testimonials, and document blocks.
- Add sorting and manual ordering.
- Add page-builder data-binding controls.
- Add template sets for property-management work, technical builds, creative work, and personal interests.
- Add public collection routes with SEO controls.

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

- Administrators can create and present projects without manually duplicating content.
- Project grids filter correctly.
- Structured records can be placed in any builder page.
- Related content works.
- Personal content remains visually editable.
- Public routes are indexable or private according to settings.
