# Shared Hosting Notes

- Point the document root at `/public` when the host supports it.
- Keep queue and mail behavior synchronous unless cron is configured.
- Run imports and exports in dry-run mode first; large packages should be split by media size if memory is constrained.
- Build assets locally before uploading a release ZIP.
- Ensure `storage/` and `bootstrap/cache/` are writable.
- Do not upload `.env` in release packages; configure production secrets on the host.
