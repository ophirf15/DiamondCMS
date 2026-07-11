# DiamondCMS — Cursor Build Plan

DiamondCMS is a self-hosted, no-code personal website CMS for résumés, portfolios, hobbies, projects, interests, contact forms, and visually distinctive personal sites.

The initial installation will power **ophiryahalom.com**, but the architecture should remain reusable enough to become a downloadable CMS for other people later.

## Core product principles

1. **WordPress-style installation**
   - Upload and extract a release ZIP.
   - Open the site in a browser.
   - Complete a guided installation wizard.
   - Configure MySQL credentials.
   - Create the initial administrator.
   - No shell access required on production hosting.

2. **No-code site building**
   - No YAML-based editing.
   - No source-code editing required.
   - Direct visual editing, drag-and-drop sections, responsive controls, reusable templates, and rich branding configuration.
   - Custom HTML may exist only as an optional advanced block inside a WYSIWYG editing experience.

3. **Shared-host compatible production**
   - PHP and MySQL.
   - Apache or LiteSpeed compatible.
   - phpMyAdmin-compatible database setup.
   - Node.js may be used locally for development and asset compilation, but must not be required on the production host.

4. **AI-assisted creation**
   - OpenAI, Anthropic Claude, and Google Gemini support.
   - Server-side encrypted API keys.
   - Model discovery where provider APIs support it.
   - AI can build pages, rewrite content, review résumés, propose site-wide changes, and generate a complete draft website.
   - AI must not silently change published content.

5. **Safe iteration and deployment**
   - Local development.
   - Git-based source control.
   - Production-ready release ZIPs.
   - Dashboard-based update detection from the DiamondCMS GitHub repository.
   - Backup, migration, health-check, and rollback support.
   - Full-site export from local and import into production, including content, configuration, and media.

## Recommended implementation direction

Use a current stable version of **Laravel** supported by the target PHP runtime, with:

- Laravel backend and database migrations
- Blade-rendered public pages
- Vue 3 or another modern component framework for the visual builder
- Vite or equivalent for local asset compilation
- Compiled production assets included in release ZIPs
- No Node.js dependency in production
- MySQL as the required database
- Composer dependencies bundled in production releases
- Server-side provider adapters for AI APIs

Do not assume a specific Laravel major version until the project begins. Verify the current stable release, its PHP requirements, and shared-host compatibility, then pin dependencies.

## How to use this package with Cursor

1. Add the entire `DiamondCMS_Cursor_Plan` folder to the repository.
2. Start with `01_MASTER_PRODUCT_SPECIFICATION.md`.
3. Give Cursor `02_ARCHITECTURE_AND_GUARDRAILS.md`.
4. Run each numbered phase in order.
5. Do not ask Cursor to implement multiple phases at once.
6. Require tests, migrations, documentation, and a phase completion report before moving forward.
7. Keep every phase deployable and reversible.
