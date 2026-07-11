# Phase 11 — Backups, Site Transfer, Deployment, and GitHub Updater

## Objective

Enable safe local-to-production transfer and controlled in-dashboard software updates.

## Cursor prompt

```text
You are implementing Phase 11: Backups, Site Transfer, Deployment, and GitHub Updater of DiamondCMS.

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
- Build database backup.
- Build application-file backup.
- Build media backup.
- Add backup retention controls.
- Add restore workflow and restore verification.
- Build complete-site export package.
- Exclude secrets by default.
- Add version manifest and checksums.
- Build import dry run.
- Build compatibility validation.
- Build URL replacement.
- Build path normalization.
- Build replace and merge modes.
- Create pre-import backup.
- Add conflict report.
- Add post-import health checks.
- Add rollback.
- Build the official DiamondCMS GitHub release checker.
- Use tagged releases.
- Display release notes and compatibility warnings.
- Download a production-ready release ZIP.
- Verify checksum or signature.
- Stage updates before activation.
- Enter maintenance mode.
- Back up files and database.
- Run migrations.
- Clear caches.
- Run health checks.
- Roll back automatically on failure.
- Add update logs.
- Add manual release ZIP upload as a recovery option.
- Document the local-build to production workflow.
- Document how to publish GitHub releases.

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

- A locally built site can be exported and imported into a clean production installation with media intact.
- Secrets are not transferred unless explicitly and securely handled.
- Failed imports restore the prior state.
- Dashboard detects an official GitHub release.
- Failed updates roll back.
- Update package integrity is verified.
- Backups can be restored successfully in a tested workflow.
