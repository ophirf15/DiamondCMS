# ADR 0001: Stack and Architecture

## Status

Accepted

## Context

DiamondCMS must run on shared hosting (PHP + MySQL only), install via browser like WordPress, and provide a no-code visual builder. Node.js may be used for development asset compilation but must not be required in production.

## Decision

| Concern | Choice |
|---------|--------|
| Framework | Laravel 13.19 (PHP 8.3+) |
| Database | MySQL 8 / MariaDB 10.6+ (MariaDB 12 acceptable locally) |
| Public site | Blade server-rendered HTML |
| Admin / builder | Vue 3 + TypeScript + Vite; compiled assets in release ZIP |
| Module layout | `app/Domains/{Domain}/` bounded contexts |
| Builder DnD | VueDraggable Plus + SortableJS |
| Tests | PHPUnit (Pest not yet compatible with Laravel 13); Vitest; Playwright by Phase 12 |
| Queue / cache | Database drivers; synchronous job fallback; no Redis required |
| Versioning | SemVer from `0.1.0` |

## Consequences

- Production release ZIP includes `vendor/` and compiled `public/build/` assets.
- Shared-host deployments need document root → `/public` or root bootstrap fallback.
- Domain modules keep features isolated while sharing one Laravel app and MySQL database.
