# DiamondCMS Release and Deployment Checklist

## Before creating a release

- [ ] All backend tests pass
- [ ] All frontend tests pass
- [ ] Browser tests pass
- [ ] Static analysis passes
- [ ] Production frontend build completes
- [ ] No secrets are committed
- [ ] `.env.example` is current
- [ ] Database migrations are reversible
- [ ] Upgrade path is tested
- [ ] Changelog is updated
- [ ] Version is updated
- [ ] Release notes are written
- [ ] Shared-host requirements are documented
- [ ] Installer is tested from a clean database
- [ ] Backup and rollback are tested

## Release package contents

- [ ] Application PHP files
- [ ] Bundled Composer dependencies
- [ ] Compiled CSS and JavaScript
- [ ] Installer
- [ ] Database migrations
- [ ] Release manifest
- [ ] Version metadata
- [ ] Checksums or signature
- [ ] Required writable directories
- [ ] Minimal installation instructions

## Release package exclusions

- [ ] No `.env`
- [ ] No API keys
- [ ] No SMTP credentials
- [ ] No database dump containing personal data
- [ ] No `node_modules`
- [ ] No local media
- [ ] No test artifacts
- [ ] No IDE metadata
- [ ] No debug logs

## Production deployment to ophiryahalom.com

- [ ] Confirm PHP version
- [ ] Confirm MySQL version
- [ ] Confirm database credentials
- [ ] Confirm document-root strategy
- [ ] Confirm HTTPS
- [ ] Confirm writable directories
- [ ] Confirm mail settings
- [ ] Confirm cron availability
- [ ] Create pre-deployment backup
- [ ] Enable maintenance mode
- [ ] Upload or update release
- [ ] Run migrations
- [ ] Clear caches
- [ ] Run health checks
- [ ] Test administrator login
- [ ] Test public homepage
- [ ] Test mobile navigation
- [ ] Test résumé page
- [ ] Test résumé PDF download
- [ ] Test contact form
- [ ] Test email delivery
- [ ] Test media
- [ ] Test AI provider connection
- [ ] Test sitemap and robots.txt
- [ ] Test analytics and cookie consent
- [ ] Exit maintenance mode
- [ ] Monitor logs

## Local-to-production content transfer

- [ ] Export complete-site package locally
- [ ] Confirm export excludes secrets
- [ ] Upload package to production
- [ ] Run import dry run
- [ ] Review conflicts
- [ ] Create production backup
- [ ] Import content and media
- [ ] Replace local URLs
- [ ] Run post-import health checks
- [ ] Verify media paths
- [ ] Verify menus
- [ ] Verify forms
- [ ] Verify theme settings
- [ ] Verify AI settings are present but keys are not transferred
