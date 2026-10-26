# Moving OneSuite to Another System

This covers moving the whole app (code + database) to a different machine or server.

## 1. What's included

- All application code (this repo, minus `vendor/`, `node_modules/`, and `.env` — none of those should be committed or copied).
- `database/exports/onesuite_full_backup.sql` — a full dump (schema + data) of the local `onesuite` MySQL database, taken fresh for this move. 235 tables, ~753 KB.
- `.env.example` — template for the environment file; copy it to `.env` on the new system and fill in real values.

## 2. Requirements on the new system

- PHP 8.3+ with the extensions Laravel/Filament need (pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, fileinfo, gd or imagick).
- MySQL 8.0+ (or MariaDB 10.6+).
- Composer 2.x.
- Node.js 18+ and npm (only needed if you'll rebuild front-end assets — `public/build` is git-ignored).

## 3. Steps

```bash
# 1. Get the code onto the new machine (git clone, or copy the folder excluding vendor/node_modules/.env)
git clone <your-repo-url> OneSuite
cd OneSuite

# 2. Install PHP dependencies
composer install --no-dev --optimize-autoloader
# (drop --no-dev if you need dev tools like Pail/Pint there too)

# 3. Install and build front-end assets (optional — only if you changed CSS/JS)
npm install
npm run build

# 4. Set up the environment
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set at minimum:

```
APP_URL=https://your-new-domain-or-ip
DB_HOST=127.0.0.1
DB_DATABASE=onesuite
DB_USERNAME=<new DB user>
DB_PASSWORD=<new DB password>
```

For production, also set `APP_ENV=production` and `APP_DEBUG=false` (already the defaults in `.env.example`).

## 4. Create the database and import the dump

```bash
mysql -u root -p -e "CREATE DATABASE onesuite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER 'onesuite'@'%' IDENTIFIED BY 'your-new-password';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON onesuite.* TO 'onesuite'@'%'; FLUSH PRIVILEGES;"

mysql -u onesuite -p onesuite < database/exports/onesuite_full_backup.sql
```

This restores everything exactly as it exists locally right now — all migrations are already applied in the dump, so you do **not** need to run `php artisan migrate` after importing it. (If you'd rather start from an empty schema and replay every migration instead of importing the dump, run `php artisan migrate` on a freshly created empty database instead — but you'll lose all existing data, since the migrations only create structure, not the records in this dump.)

## 5. Finish the setup

```bash
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Point your web server's document root at `public/` (Apache/Nginx), or for a quick check:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Log in with the same credentials as the local copy (`admin@admin.com` / `admin`), since the dump carries over all existing users.

## 6. Things to double-check after moving

- File permissions: `storage/` and `bootstrap/cache/` must be writable by the web server user.
- If you changed `APP_URL`, check any hardcoded links in custom content (none are hardcoded in the app code itself — all routes use `route()` helpers).
- Queue worker / scheduler: if you rely on `php artisan schedule:run` via cron or a queue worker process, set those up on the new system (they aren't started automatically).
- `storage/app/private` (local disk root) holds uploaded files — copy that directory across too if you need existing uploads to carry over; the SQL dump only has database rows, not files on disk.
