# DiamondCMS Administrator Manual

## Core Workflows
- Use `/admin` after installation to manage content through authenticated admin-only routes.
- Create portfolio categories and projects before placing featured-project builder blocks.
- Create forms with a versioned field schema, publish them, then embed or link to `/forms/{slug}`.
- Configure SMTP in admin settings; passwords are encrypted and never displayed again.
- Configure AI providers with encrypted keys, generate drafts, preview output, then approve to create a draft page and revision.
- Use backups before imports and before staged updates.

## Safety Rules
- AI never publishes content automatically.
- Secrets are excluded from exports.
- Webhooks and SaaS billing are intentionally absent.
