# Phase 8 — Forms, Global Email, and Submission Management

## Objective

Build the no-code form builder, SMTP settings, secure processing, and submission administration.

## Cursor prompt

```text
You are implementing Phase 8: Forms, Global Email, and Submissions of DiamondCMS.

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
- Build global SMTP configuration.
- Encrypt SMTP passwords.
- Add test-email function.
- Add email templates.
- Add delivery logs without secrets.
- Build drag-and-drop form builder.
- Add all field types in the master specification.
- Add conditional logic.
- Add validation rules.
- Add honeypot and rate limiting.
- Add Cloudflare Turnstile.
- Add CAPTCHA provider abstraction.
- Add administrator notifications.
- Add confirmation emails.
- Store submissions.
- Add search, filters, status, notes, archive, deletion, retention, and CSV export.
- Add secure file uploads.
- Add privacy consent controls.
- Add custom success messages and redirects.
- Add builder-integrated form styling.
- Add spam and abuse audit events.
- Do not implement webhooks in this phase.

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

- Contact and custom forms can be built without code.
- SMTP test succeeds with valid settings.
- Spam controls work.
- Submission data is access-controlled.
- Uploaded files are validated and protected.
- CSV export works.
- SMTP credentials never reach the browser.
