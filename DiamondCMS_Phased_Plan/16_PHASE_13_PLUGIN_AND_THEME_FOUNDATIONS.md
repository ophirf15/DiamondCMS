# Phase 13 — Plugin and Theme Foundations

## Objective

Add carefully constrained extension points after the core platform is stable.

## Cursor prompt

```text
You are implementing Phase 13: Plugin and Theme Foundations of DiamondCMS.

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
- Design plugin and theme manifests.
- Add version and compatibility constraints.
- Add install, enable, disable, update, and uninstall lifecycle.
- Add permission declarations.
- Add signed package verification.
- Add extension sandboxing where feasible.
- Add hooks and events with documented contracts.
- Add theme inheritance or token override model.
- Add third-party repository architecture without enabling arbitrary repositories by default.
- Add extension health checks.
- Add safe-mode startup.
- Add recovery when an extension breaks admin or public rendering.
- Add extension update support.
- Add developer documentation and example extension.
- Keep core features independent of optional extensions.

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

- A sample plugin and theme can be installed and removed.
- Broken extensions can be disabled through safe mode.
- Compatibility checks prevent unsafe activation.
- Extensions cannot silently access secrets without declared permissions.
- Core updates do not require extensions.
