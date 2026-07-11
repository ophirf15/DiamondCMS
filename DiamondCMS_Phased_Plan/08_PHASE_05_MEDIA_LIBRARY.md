# Phase 5 — Media Library

## Objective

Build a comprehensive media and document management system.

## Cursor prompt

```text
You are implementing Phase 5: Media Library of DiamondCMS.

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
- Implement bulk and drag-and-drop upload.
- Add folder-like organization and tags.
- Add search and filters.
- Add image preview and metadata.
- Add alt text, captions, credits, and focal points.
- Add crop, resize, rotate, and compression.
- Generate responsive variants.
- Generate WebP.
- Generate AVIF when supported.
- Add PDF and document uploads.
- Add PDF preview.
- Add duplicate detection.
- Add media usage tracking.
- Add replace-file while preserving references.
- Add unused-media report.
- Add safe bulk deletion with dependency warnings.
- Add storage quota and file-size configuration.
- Validate MIME type and file contents.
- Sanitize SVG or disable it by default until safely supported.
- Re-encode uploaded raster images where appropriate.
- Add chunked uploads for shared-host limits.
- Integrate media picker into the visual builder.

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

- Images, PDFs, and documents can be uploaded and selected in the builder.
- Malicious or mismatched files are rejected.
- Replacing a file preserves page references.
- Deleting in-use media requires explicit confirmation.
- Responsive images are rendered correctly.
- Media operations work under shared-host memory limits.
