# Cursor Workflow and Phase Completion Template

## One-phase-at-a-time rule

Do not give Cursor the full implementation request in one prompt. Use the phase files sequentially.

At the start of each phase, provide:

1. Master product specification
2. Architecture and guardrails
3. Current phase document
4. Prior completion report
5. Any screenshots or UX references relevant to that phase

## Required Cursor behavior

Cursor must not:

- Implement later phases
- Replace production requirements with placeholders
- Add a production Node.js runtime
- Add YAML as an administrator-facing editing format
- Store builder pages as arbitrary HTML only
- Expose API keys
- Use unverified update packages
- Make AI changes silently
- Remove migrations
- Break rollback compatibility
- Commit local secrets
- Assume shell access on production
- Require Redis
- Require Docker
- Turn DiamondCMS into a blog platform
- Add public registration

## Phase completion report template

```markdown
# Phase Completion Report

## Phase

[Phase name and number]

## Summary

[What was implemented]

## Architecture decisions

[Important decisions and why]

## Files changed

[List of major files and directories]

## Database changes

- Migration:
- Tables:
- Indexes:
- Rollback behavior:

## Security controls

[Authentication, authorization, validation, sanitization, encryption, logging]

## Tests

- Unit:
- Feature:
- Browser:
- Accessibility:
- Build:
- Static analysis:

## Commands executed

```bash
[commands]
```

## Results

[Pass/fail summary]

## Manual verification

1. ...
2. ...
3. ...

## Shared-host verification

- PHP-only production runtime:
- MySQL:
- Apache/LiteSpeed:
- No Node runtime:
- Writable directories:
- Scheduler fallback:

## Known limitations

[List]

## Deferred work

[List, tied to future phases]

## Upgrade notes

[How this phase affects updates and rollback]

## Ready for next phase

Yes / No
```

## Change-control rule

When Cursor proposes a new framework, package, or service:

1. Explain why it is required.
2. Confirm production compatibility.
3. Confirm license compatibility.
4. Confirm maintenance status.
5. Confirm security implications.
6. Compare at least one alternative.
7. Record the decision in an architecture decision record.

## Debugging rule

When a phase fails:

1. Preserve the failing state.
2. Capture exact error output.
3. Identify whether failure is environment, migration, frontend build, backend, permission, or data related.
4. Fix the smallest root cause.
5. Add a regression test.
6. Re-run the complete phase verification.
