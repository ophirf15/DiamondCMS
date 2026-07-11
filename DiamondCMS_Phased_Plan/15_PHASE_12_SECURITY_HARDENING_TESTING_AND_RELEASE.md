# Phase 12 — Security Hardening, Testing, and Initial Production Release

## Objective

Perform system-wide validation, hardening, regression testing, documentation, and release preparation.

## Cursor prompt

```text
You are implementing Phase 12: Security Hardening, Testing, and Initial Production Release of DiamondCMS.

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
- Perform threat modeling.
- Audit authentication and authorization.
- Audit CSRF, XSS, SQL injection, SSRF, path traversal, upload handling, and stored custom code.
- Audit AI data exposure.
- Audit installer and updater.
- Add secure headers and Content Security Policy configuration.
- Add dependency vulnerability scanning.
- Add backup and restore drills.
- Add update rollback drills.
- Add import/export compatibility tests.
- Add browser tests for installer, login, builder, publishing, resume, forms, AI approval, export/import, and update.
- Add load and memory tests appropriate to shared hosting.
- Add accessibility regression tests.
- Add production configuration validation.
- Add privacy and data-retention documentation.
- Add administrator manual.
- Add developer setup guide.
- Add release-building guide.
- Add troubleshooting guide.
- Build release candidate.
- Test clean install.
- Test upgrade from each supported prior version.
- Produce signed or checksummed production release.

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

- No known critical security issues remain.
- Core workflows pass automated browser tests.
- Clean installation succeeds.
- Upgrade succeeds.
- Backup and rollback are tested.
- Release ZIP is production-ready.
- Administrator documentation is complete.
- ophiryahalom.com deployment checklist is ready.
