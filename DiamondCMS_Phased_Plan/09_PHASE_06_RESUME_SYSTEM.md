# Phase 6 — Résumé Import, Editing, Versions, and PDF Export

## Objective

Build the structured résumé system, AI-assisted import, multiple variants, visual résumé pages, and PDF generation.

## Cursor prompt

```text
You are implementing Phase 6: Résumé System of DiamondCMS.

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
- Create structured models for profile, experience, education, skills, certifications, awards, and résumé versions.
- Support PDF and DOCX uploads.
- Extract text server-side using appropriate PHP-compatible libraries or safe bundled tools.
- Add optional AI-assisted structured parsing.
- Add a review-and-correction workflow before saving imported data.
- Preserve the original uploaded file.
- Support multiple résumé variants.
- Allow variant-specific summary, bullets, ordering, visibility, and skills.
- Add résumé-specific builder blocks.
- Add ATS-friendly template.
- Add rich visual template inspired by the supplied dark sidebar résumé layout.
- Add public résumé webpage.
- Add friendly share URL.
- Add public/private visibility.
- Add expiring private share links.
- Add print-ready PDF export.
- Ensure exported PDFs render predictably without requiring Node.js on production.
- Add download button block and file-version management.
- Add AI résumé review, bullet strengthening, gap analysis, and tone controls.

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

- A PDF or DOCX résumé can be imported.
- Parsed fields are not committed until reviewed.
- Multiple résumé variants can coexist.
- Public résumé pages are responsive.
- PDF output is stable and print-ready.
- ATS template avoids layout features that commonly break text extraction.
- API keys and private résumé drafts remain server-side.
