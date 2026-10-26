# OneSuite

A multi-client business suite: one installation serves many client companies, each seeing only its own data.

- **HRM** (complete): employees, org chart, recruitment, onboarding, attendance, leave, shifts, payroll, performance, training, assets, travel, expenses, documents, separation, employee and manager self-service.
- **Settings**: users, roles and permissions, branches, departments, holidays, workflows, message templates.
- **CRM, Inventory, Sales**: placeholders, not built yet.

Built with Laravel 13 on PHP 8.3 and MySQL / MariaDB. The interface uses its own theme in `public/vendor/metrica`, so Node.js is **not** needed to run it.

---

## Requirements

| | |
|---|---|
| PHP | 8.3 or newer, with `ctype dom fileinfo filter iconv intl mbstring openssl pdo_mysql session simplexml tokenizer xmlreader zip` (`composer check-platform-reqs` lists anything missing) |
| Composer | 2.x |
| Database | MySQL 8 or MariaDB 10.4+, database created as `utf8mb4` |
| Web server | `php artisan serve` for a single machine, or Apache / Nginx pointing at the `public/` folder |

Turn on **OPcache** in `php.ini` (`zend_extension=opcache`, `opcache.enable=1`). Without it every page re-reads hundreds of PHP files and can take seconds.

---

## 1. Fresh install (new system, no existing data)

```bash
# 1. Get the code into a folder, then inside it:
composer install --no-dev --optimize-autoloader
cp .env.example .env              # Windows: copy .env.example .env
php artisan key:generate
```

2. Create an empty database:

```sql
CREATE DATABASE onesuite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

3. Set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` and `APP_URL` in `.env`, then:

```bash
php artisan migrate --force
php artisan db:seed --force       # the permission list
php artisan storage:link          # makes uploaded photos and documents reachable
php artisan onesuite:install      # asks for the logins below
```

`onesuite:install` creates:

- **Platform admin**: you, the provider. Signs in and lands on `/platform/clients` to add clients, choose their apps and set user limits.
- **First client** (optional): the company, its Head Office branch, its admin login, and ready-made HR Manager / Manager / Employee roles.

It can run unattended with options, for example `php artisan onesuite:install --no-interaction --platform-email=... --platform-password=... --company="Acme Ltd" --admin-name="..." --admin-email=... --admin-password=...`. Running it again changes nothing that already exists.

For a demo system with sample employees and requests, also run `php artisan db:seed --class=DemoDataSeeder`.

---

## 2. Moving an existing OneSuite to another system

### On the old system

```powershell
powershell -ExecutionPolicy Bypass -File scripts\export-for-move.ps1
```

This creates `OneSuite-move-<date>.zip` on the Desktop. Pass `-Destination` to put it elsewhere, and `-MysqlBin` if `mysqldump.exe` isn't in `E:\vivek\mysql\bin`. The zip holds:

- `OneSuite\`: the code and `.env`, plus uploaded files (`storage/app`). Left out: `vendor`, logs, caches, old backups and the old SQLite file.
- `onesuite-database.sql`: a full export of the database.
- `READ-ME-FIRST.txt`: the steps below.

Run the script **last**, just before switching over, so the export has the latest data. The zip contains your data and your `APP_KEY`, so keep it private and never put it inside a web folder (the script refuses to).

### On the new system

```bash
# 1. Unzip, then create an empty utf8mb4 database (see Fresh install, step 2)
mysql -u <user> -p onesuite < onesuite-database.sql

# 2. In OneSuite/.env: change DB_* and APP_URL for this system.
#    Do NOT change APP_KEY.

# 3. In the OneSuite folder:
composer install --no-dev --optimize-autoloader
php artisan migrate --force       # usually "Nothing to migrate"
php artisan storage:link
```

Everyone signs in with the same logins and passwords as before.

**Why `APP_KEY` must not change:** saved mail-account passwords and platform settings are encrypted with it. With a new key they can't be read, and sessions and invite links stop working. Passwords themselves are hashed and are not affected.

---

## 3. Going live on a server

In `.env`:

```dotenv
APP_ENV=production
APP_DEBUG=false                   # never true on a live server: errors would show code and settings
APP_URL=https://hr.yourcompany.com
SESSION_SECURE_COOKIE=true        # when served over https
MAIL_MAILER=smtp                  # plus MAIL_HOST / MAIL_PORT / MAIL_USERNAME / MAIL_PASSWORD / MAIL_FROM_ADDRESS
```

Clients can also connect their own mailbox later in **Settings → Mail setup**. Until mail is configured, invite links are shown on screen after creating a user, so nothing is blocked.

**Web server:** the document root must be the `public/` folder, never the project folder.

Apache:

```apache
<VirtualHost *:80>
    ServerName hr.yourcompany.com
    DocumentRoot /var/www/OneSuite/public
    <Directory /var/www/OneSuite/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Nginx:

```nginx
server {
    server_name hr.yourcompany.com;
    root /var/www/OneSuite/public;
    index index.php;
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

**Folder permissions:** the web server user must be able to write to `storage/` and `bootstrap/cache/`. On Linux: `chown -R www-data:www-data storage bootstrap/cache`.

**After every update of the code:**

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize              # caches config, routes and views
```

After editing `.env` on a server that ran `optimize`, run `php artisan optimize:clear`, then `php artisan optimize` again, or the change is ignored.

**No background workers needed:** there is no queue worker or cron job to run. Mail is sent while the request runs.

**Backups:** back up two things daily, the database (`mysqldump`) and the `storage/app` folder (uploaded photos and documents).

**Before going live:**

- Remove or change any demo logins.
- Use a database user that has rights to the OneSuite database only, with a password.

---

## How access works (for whoever runs it)

- **Clients:** the platform admin adds companies at `/platform/clients`. Each client gets only the apps you tick, and optionally a user limit. A client's users never see another client's data.
- **Roles** have two parts:
  - **Permissions**: which screens and actions.
  - **Data access**:
    - **All employees** (HR)
    - **Their team** (everyone below them in the reporting line)
    - **Only themselves**
- **Reporting line:** set "Reports to" on each employee, or drag people around on **HRM → Employees → Org Chart**. It drives the Team screens and the "Their team" data access.
- **Approval levels:** **HRM → Settings → Approval Levels** sets how many managers up the reporting line must approve leave, attendance regularization, work from home, overtime, travel and expense requests. The default is 1.
- **Logins and employees:** each login should be linked to its employee record (Settings → Users → "This login is for"). That gives them their "My …" self-service screens, and gives Team or Own roles someone to work from.

---

## Development

```bash
php artisan serve                 # http://localhost:8000
php artisan test                  # uses an in-memory SQLite database (phpunit.xml)
```

Migrations must keep working on both MySQL and SQLite (tests).
