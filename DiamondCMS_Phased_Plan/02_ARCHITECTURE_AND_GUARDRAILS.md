# DiamondCMS Architecture and Engineering Guardrails

## Recommended architecture

Build DiamondCMS as a Laravel-based modular monolith.

### Backend

- Current stable Laravel release compatible with the target host
- PHP 8.2+ or framework-required minimum
- MySQL
- Eloquent ORM
- Database migrations
- Service classes
- Jobs with synchronous fallback
- Events and listeners where useful
- Policies and middleware for authorization
- Storage abstraction for media
- Blade for public rendering
- REST-like internal JSON endpoints for builder operations
- CSRF-protected admin routes

### Admin and builder

- Vue 3 or equivalent component-based frontend
- TypeScript
- Vite for local builds
- Compiled assets committed to or included in release artifacts
- No Node runtime in production
- Drag-and-drop library selected after technical evaluation
- State store with undo/redo history
- Validated builder schema
- Autosave with conflict detection

### Public rendering

- Server-rendered HTML
- Progressive enhancement
- Minimal client JavaScript
- Semantic markup
- Responsive design
- Accessible keyboard navigation
- Search-engine friendly output
- Cacheable page responses

## Modular domains

Organize code into clear bounded modules:

- Core
- Installer
- Authentication
- Administration
- Users
- Settings
- Sites
- Pages
- Builder
- Themes
- Templates
- Navigation
- Media
- Resume
- Portfolio
- Collections
- Forms
- Mail
- AI
- SEO
- Redirects
- Analytics
- Accessibility
- ExportImport
- Backups
- Updates
- Audit
- Health
- Extensions

Do not build a distributed microservice architecture.

## Data-storage principles

- MySQL is authoritative.
- Builder content is structured JSON validated against versioned schemas.
- Structured résumé, project, skill, form, and media records use normalized tables.
- Secrets use application-level encryption.
- Never store raw API keys in logs.
- Never store provider keys in browser storage.
- Keep immutable revisions for publishable content.
- Store media metadata in the database and binary files in configured storage.

## Release architecture

The repository should distinguish:

- Source code
- Local development assets
- Tests
- Documentation
- Build scripts
- Production release package

A production release ZIP must include:

- PHP application
- `vendor` dependencies
- Compiled JS and CSS
- Installer
- Required writable-directory placeholders
- Release manifest
- Version
- Checksums
- Upgrade migrations
- Installation documentation

It must exclude:

- `.env`
- Development secrets
- `node_modules`
- Test databases
- Local uploads
- IDE files
- Uncompiled source maps unless intentionally enabled

## Shared-host compatibility

Design for:

- Document root pointing to `/public` when supported
- Safe root-level fallback bootstrap when the host cannot change document root
- Apache rewrite rules
- LiteSpeed compatibility
- Writable storage and cache directories
- No long-running workers required
- Synchronous mail/job fallback
- Optional cron-based scheduled tasks
- Web-triggered scheduler diagnostics
- Memory-conscious imports
- Chunked upload and import processing

## Builder safety

The visual builder must not directly persist arbitrary executable code.

Use:

- Component registry
- Property schemas
- Style tokens
- Responsive override objects
- Sanitized rich text
- Sanitized custom HTML
- Restricted custom JavaScript
- Render adapters
- Migration functions for older builder-schema versions

Every component needs:

- Unique type identifier
- Version
- Default props
- Validation schema
- Admin editor
- Public renderer
- Accessibility behavior
- Responsive behavior
- Serialization tests

## Quality gates

Every phase must include:

- Database migrations
- Rollback paths
- Authorization checks
- Validation
- Automated tests
- Basic accessibility testing
- Documentation
- Changelog entry
- No unresolved critical static-analysis errors
- No secrets committed
- No broken production build
- Upgrade compatibility from the prior phase

## Cursor execution rule

For every phase, Cursor must:

1. Inspect the existing repository.
2. Summarize the current architecture.
3. Identify conflicts with the phase.
4. Propose a short implementation plan.
5. Implement only the requested phase.
6. Add tests.
7. Run relevant tests and builds.
8. Update documentation.
9. Report changed files.
10. Report migrations.
11. Report manual verification steps.
12. Stop and wait for approval.

Do not let Cursor redesign completed modules without explaining why.
Do not let Cursor replace working code with placeholders.
Do not accept mock-only implementations for production requirements.
