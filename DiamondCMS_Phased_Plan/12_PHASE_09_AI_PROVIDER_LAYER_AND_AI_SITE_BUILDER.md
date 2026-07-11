# Phase 9 — AI Provider Layer and AI Site Builder

## Objective

Create secure multi-provider AI integration and controlled AI-assisted content and site generation.

## Cursor prompt

```text
You are implementing Phase 9: AI Provider Layer and AI Site Builder of DiamondCMS.

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
- Build provider adapters for OpenAI, Anthropic Claude, and Google Gemini.
- Store API keys encrypted server-side.
- Add provider connection tests.
- Discover available models through provider APIs when supported.
- Add manual model fallback.
- Add default provider and per-task model settings.
- Add token and estimated-cost logs where possible.
- Add monthly usage limits.
- Add global and per-admin disable controls.
- Build prompt-template registry and versioning.
- Add AI actions for rewrite, tone, shorten, expand, proofread, SEO, résumé review, project analysis, accessibility review, consistency review, and content-gap review.
- Add selected-text AI tools in the builder.
- Add page-generation workflow using existing components.
- Allow AI-generated component configurations.
- Allow sandboxed custom HTML and CSS generation with sanitization and preview.
- Do not allow AI-generated PHP or executable backend code.
- Build full-site questionnaire.
- Generate a complete unpublished draft site from answers.
- Build site-wide analysis and change-plan workflow.
- Require approval before site-wide apply.
- Create revisions before every applied AI change.
- Add diffs and preview.
- Add undo and rollback.
- Add AI audit log.
- Prevent protected secrets and form submissions from being sent by default.
- Add explicit context-selection UI.

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

- Each provider can be configured independently.
- Keys never appear in browser source, network payloads, logs, or exports.
- Model lists refresh safely.
- AI can create an unpublished page using builder components.
- AI can generate a complete unpublished draft website.
- Site-wide changes require a plan and approval.
- Every applied change creates a revision.
- AI cannot execute backend code or shell commands.
