# Phase 1 — Web Installer and Authentication

## Objective

Create the WordPress-style installation wizard, administrator accounts, security baseline, and recovery flows.

## Cursor prompt

```text
You are implementing Phase 1: Web Installer and Authentication of DiamondCMS.

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
- Build a browser-based installation wizard.
- Check PHP version, extensions, writable directories, URL rewriting, and database connectivity.
- Collect MySQL configuration securely.
- Create or validate the database.
- Write environment configuration safely.
- Generate application key.
- Run migrations.
- Create site name, base URL, and first administrator.
- Lock installer after completion.
- Add multiple equal-access administrator accounts.
- Add login, logout, password reset, session management, login throttling, and two-factor authentication.
- Add administrator creation, editing, disabling, and deletion safeguards.
- Add an activity log for authentication and administrator changes.
- Add production-safe error handling.
- Add install recovery for interrupted setup.
- Add CLI installation only as an optional developer convenience, not a production requirement.

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

- A clean release ZIP installs from the browser.
- Installer cannot be reopened after completion without an explicit secure reset process.
- Administrator login and password reset work.
- 2FA can be enabled and recovered safely.
- Brute-force throttling works.
- No credentials appear in logs or browser responses.
- Installation succeeds on a representative shared-host environment.
