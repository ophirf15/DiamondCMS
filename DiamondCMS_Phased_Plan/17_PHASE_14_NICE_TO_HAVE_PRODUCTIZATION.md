# Phase 14 — Nice-to-Have Productization

## Objective

Prepare DiamondCMS for optional distribution to other users without compromising the original personal-site focus.

## Cursor prompt

```text
You are implementing Phase 14: Nice-to-Have Productization of DiamondCMS.

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
- Add white-label mode.
- Replace DiamondCMS logo and name in permitted admin surfaces.
- Add custom login branding.
- Add custom admin accent colors.
- Add role-based permissions: Owner, Administrator, Designer, Content Editor, Form Manager.
- Add invitation workflow.
- Add optional license-key architecture.
- Add installation identifiers.
- Add opt-in telemetry only.
- Add privacy controls for telemetry.
- Add public downloadable-package workflow.
- Evaluate private repository updates.
- Evaluate stable and beta channels.
- Evaluate hosted-service and SaaS readiness.
- Evaluate multi-site without implementing it unless separately approved.
- Document productization risks, support burden, security model, and licensing options.

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

- White-label configuration is isolated from core functionality.
- Roles enforce least privilege.
- Telemetry is off by default.
- Licensing architecture does not block private self-hosted use.
- SaaS and multi-site remain evaluation documents unless explicitly approved.
