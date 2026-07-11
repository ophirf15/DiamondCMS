# Apache and LiteSpeed deployment

Point the virtual host document root to the `public` directory. The production ZIP includes compiled assets and `vendor`, so Node.js is not required on the server.

Example Apache virtual host:

```apache
<VirtualHost *:80>
    ServerName example.com
    DocumentRoot /home/example/diamondcms/public

    <Directory /home/example/diamondcms/public>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>
</VirtualHost>
```

LiteSpeed shared hosting should use the same public directory. If the host cannot point directly to `public`, place the app one level above `public_html` and copy the contents of `public` into `public_html`, then update `index.php` paths to reference the app directory.

Cron for scheduled publishing:

```bash
* * * * * /usr/bin/php /home/example/diamondcms/artisan diamondcms:publish-scheduled >/dev/null 2>&1
```

If cron is unavailable, call the web fallback from a host scheduler:

```text
POST https://example.com/scheduler/publish?token=DIAMONDCMS_SCHEDULER_TOKEN
```

Never place `.env`, `storage`, or the project root under a public directory.
