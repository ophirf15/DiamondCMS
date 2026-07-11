# ophiryahalom.com Deployment Checklist

1. Build the RC ZIP with `php scripts/build-release.php`.
2. Verify the ZIP checksum against the generated `.sha256` file.
3. Upload the ZIP to the hosting account.
4. Configure the production `.env` manually; do not upload local secrets.
5. Confirm the web root points to `/public`.
6. Run migrations with `php artisan migrate --force`.
7. Visit `/install` only on a clean install; otherwise verify `/login` and `/admin`.
8. Re-enter SMTP and AI provider credentials.
9. Create a backup before importing local content.
10. Confirm `/projects`, `/sitemap.xml`, `/robots.txt`, forms, AI draft approval, export, and update staging.
