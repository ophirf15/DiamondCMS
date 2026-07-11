# Phase 3 — Visual Page Builder Core

## Objective

Implement the hybrid no-code visual page builder and its structured rendering engine.

## Cursor prompt

```text
You are implementing Phase 3: Visual Page Builder Core of DiamondCMS.

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
- Define and version the structured builder JSON schema.
- Build component registry, renderer contracts, validation, and schema migrations.
- Implement visual canvas.
- Implement left component panel.
- Implement right properties panel.
- Implement structure/layers panel.
- Implement drag-and-drop sections and blocks.
- Implement columns and responsive layout.
- Implement inline text editing.
- Implement desktop, tablet, and mobile previews.
- Implement responsive style overrides.
- Implement undo and redo.
- Implement autosave and crash recovery.
- Implement keyboard shortcuts.
- Implement copy, paste, duplicate, lock, hide, and delete.
- Implement reusable sections and global sections.
- Implement page and section templates.
- Implement draft/published separation.
- Add initial blocks: heading, paragraph, rich text, image, button, divider, spacer, icon, quote, container, columns, hero, call to action.
- Ensure server-side rendering of builder pages.
- Sanitize all rich text and custom content.
- Add accessibility labels and keyboard operation for the builder itself.

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

- A complete responsive page can be built without code.
- Builder state survives refresh and crashes.
- Undo/redo works predictably.
- Published content is rendered server-side.
- Invalid builder payloads are rejected.
- Older schema versions can be migrated.
- Builder is usable by keyboard for core operations.
- Mobile preview and responsive overrides are functional.
