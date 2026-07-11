# Phase 0 — Discovery and Foundation

## Objective

Establish the repository, select exact framework versions, prove shared-host deployment viability, and create the engineering foundation.

## Cursor prompt

```text
You are implementing Phase 0: Discovery and Foundation of DiamondCMS.

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
- Verify the current stable Laravel version and its PHP requirements.
- Choose and document the exact supported PHP and MySQL versions.
- Create a Laravel modular-monolith repository.
- Configure Blade, Vue 3, TypeScript, and Vite.
- Create environment templates without secrets.
- Add local development instructions.
- Add coding standards, linting, formatting, static analysis, and test tooling.
- Add CI for PHP tests, frontend build, and static analysis.
- Establish module boundaries and directory conventions.
- Add semantic versioning.
- Add a production release build script that bundles Composer dependencies and compiled assets.
- Create a minimal public landing page and protected admin shell.
- Prove the production package runs without Node.js.
- Document Apache/LiteSpeed rewrite and public-directory deployment options.
- Add health-check endpoints with safe public and authenticated detail levels.

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

- Fresh clone can be installed locally.
- Frontend assets compile.
- PHP tests run.
- Production release ZIP is generated.
- Release ZIP contains no node_modules or secrets.
- Release ZIP runs with PHP and MySQL only.
- Admin shell and public page render.
- Architecture decision record is committed.
