# cPanel & Local Deployment Guide — DarsYar (درسیار)

## Hosting Requirements
- Apache with `mod_rewrite` enabled (or PHP 8.2+ built-in server via `START_DARSYAR.bat` for local XAMPP development)
- PHP 8.2+ with extensions: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`, `json`
- MySQL 8.0+ or MariaDB 10.4+ (`utf8mb4_unicode_ci`)
- HTTPS SSL certificate in production (AutoSSL / Let's Encrypt in cPanel)
- Zero Node.js or Docker runtime dependencies in production

## Local XAMPP Execution (Windows)

1. Run `START_DARSYAR.bat` from the project root directory. It checks/starts XAMPP MariaDB cleanly (without resetting existing database data), runs pending idempotent migrations (`bin/migrate.php`), prevents duplicate HTTP servers, opens `http://127.0.0.1:8000/login`, and runs `php -S 127.0.0.1:8000 -t public`.
2. Run `STOP_DARSYAR.bat` to stop only the DarsYar PHP local server while leaving shared XAMPP MySQL/Apache untouched.

## cPanel Production Deployment

1. **Upload Files**: Upload the project archive to your domain root (e.g., `public_html/`) and extract it.
   - Root `.htaccess` automatically forwards web requests to `public/index.php` and denies HTTP access to `app/`, `config/`, `database/`, `lang/`, `storage/`, `tests/`, `bin/`, and `.env`.
2. **Create Database**: In cPanel -> *MySQL Databases*, create a database and user with full privileges.
3. **Configure `.env`**: Copy `.env.example` to `.env`, set `APP_ENV=production`, `APP_DEBUG=false`, `APP_DEFAULT_LOCALE=fa`, `APP_URL=https://yourschool.example`, `DB_*` credentials, and generate a strong random `APP_KEY`.
4. **Run Migrations & Initial Seed**:
   - Via cPanel Terminal / SSH:
     ```bash
     php bin/migrate.php
     php bin/seed.php
     ```
   - Or import `database/schema_mysql.sql` via phpMyAdmin (generated via `php bin/migrate.php --export-sql`).
5. **Directory Permissions**: Ensure `storage/logs`, `storage/sessions`, and `storage/uploads` are writable (`755` or `775`).
6. **Rotate Accounts**: Immediately update default admin passwords upon first production login. Notice that dev demo credentials are never rendered on the login page when `APP_ENV=production`.
7. **Configure Skyroom (Optional)**: In Super Admin -> Settings -> Skyroom, enter your Skyroom API endpoint and key and click **Test Skyroom Connection**. Until configured, teachers can provide fallback meeting URLs.
