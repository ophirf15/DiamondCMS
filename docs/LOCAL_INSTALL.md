# Local install

DiamondCMS targets PHP 8.3 and MySQL/MariaDB. For the provided Windows setup, start MariaDB if it is not already running:

```powershell
& "C:\Program Files\MariaDB 12.3\bin\mariadbd.exe" --datadir="C:\Program Files\MariaDB 12.3\data" --port=3306
```

Create a local database and user if needed:

```sql
CREATE DATABASE diamondcms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'diamondcms'@'localhost' IDENTIFIED BY 'diamondcms_local';
GRANT ALL PRIVILEGES ON diamondcms.* TO 'diamondcms'@'localhost';
FLUSH PRIVILEGES;
```

Then install dependencies and run the app:

```powershell
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve
```

Open `/install` for the browser wizard, or use the developer-only CLI installer:

```powershell
php artisan diamondcms:install --admin=admin@example.com --password="change-this-password"
```

The installer writes `storage/app/installed.lock` after setup. A fresh clone should not contain that file.
