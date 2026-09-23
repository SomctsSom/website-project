# Website Management System (WMS)

Pure PHP 8.2+ website management platform with three applications:

| App | Folder | Role | Default URL |
|-----|--------|------|-------------|
| Website Core (`wc`) | `wc/` | Sole MySQL owner + HTTP APIs | http://127.0.0.1:8090 |
| Website Admin (`wa`) | `wa/` | Admin UI (calls Core over HTTP) | http://127.0.0.1:8091 |
| Public Website (`pw`) | `pw/` | Public site (calls Core public APIs) | http://127.0.0.1:8092 |

**No PHP framework, no MVC, no ORM.** Soft delete only (no SQL `DELETE` for manageable records). Extensionless URLs.

## Requirements

- PHP 8.2+ with extensions: `pdo_mysql`, `curl`, `fileinfo`, `json`
- MySQL 8 or MariaDB 11.x
- Apache 2.4 with `mod_rewrite` (or PHP built-in server for local smoke tests)

## Architecture rules

1. **Only `wc` connects to MySQL** via PDO. `wa` and `pw` never import Core DB config and must not open PDO connections.
2. Admin authentication: user logs in through Core `/api/auth/login`. Core returns an opaque session token + CSRF token. **Website Admin stores both in its PHP session** and sends them server-side as `Authorization: Bearer …` and `X-CSRF-Token`. Tokens are never placed in public JavaScript as privileged API keys.
3. Same-site local setup uses separate ports on `127.0.0.1`. For production, put Core and Admin on related hosts and keep Admin→Core calls server-side.
4. Timestamps are stored in **UTC**. Admin lists display times in **Africa/Mogadishu**.
5. Soft-delete uniqueness uses generated columns (`*_active`) so only non-deleted rows participate in unique indexes.

## Environment

Copy examples and edit:

```bash
cp wc/.env.example wc/.env
cp wa/.env.example wa/.env
cp pw/.env.example pw/.env
```

Key variables (`wc/.env`):

| Variable | Purpose |
|----------|---------|
| `DB_*` | MySQL connection (Core only) |
| `WC_BASE_URL` / `WA_BASE_URL` / `PW_BASE_URL` | Public base URLs |
| `WC_API_URL` | Base URL used by WA/PW HTTP clients |
| `APP_TIMEZONE` | `UTC` |
| `APP_DISPLAY_TIMEZONE` | `Africa/Mogadishu` |
| `MENU_MAX_DEPTH` | Max menu nesting (default 3) |
| `UPLOAD_MAX_BYTES` | Hero image size limit |
| `LOGIN_MAX_ATTEMPTS` / `LOGIN_LOCKOUT_SECONDS` | Login rate limit |

Never commit real `.env` files.

## Database setup

```bash
cd /var/www/html/project
php wc/bin/migrate.php
php wc/bin/create_super_admin.php --email=you@example.com --name="Super Admin" --password='your-strong-password'
# Or omit --password to be prompted securely
```

`migrate.php` creates the database if needed, applies `wc/database/migrations/*.sql`, then idempotent `wc/database/seeds/*.sql`.

Alternatively, import the full dump (schema + current data) in one step:

```bash
mysql -u root -p < wc/database/full_schema.sql
```

**There is no default admin password.** Bootstrap creates the first Super Admin only via CLI. After importing `full_schema.sql`, local sample users may already exist (change passwords before production use).

### Seeded data

- Roles: Super Admin, Admin, Editor
- Permissions for users/roles/permissions/menus/pages/hero/trash/audit/dashboard (`view/create/edit/delete/restore` where applicable)
- Admin navigation groups: Dashboard, Website Management, Access Control, System Operations
- Website pages: Home (`home`), About (`about`)
- Admin page metadata with whitelisted `template_key` values

## Local Apache setup

Configs live in `apache/`:

```bash
sudo cp apache/wc-8090.conf /etc/apache2/sites-available/
sudo cp apache/wa-8091.conf /etc/apache2/sites-available/
sudo cp apache/pw-8092.conf /etc/apache2/sites-available/
sudo a2enmod rewrite headers
sudo a2ensite wc-8090 wa-8091 pw-8092
sudo systemctl reload apache2
```

Document roots:

- Core: `wc/public`
- Admin: `wa/public`
- Public: `pw/public`

Private trees (`config/`, `includes/`, `database/`, `storage/`, `uploads/` raw paths) must not be web-accessible. Images are served only through Core `/media/...`.

### PHP built-in server (verification without Apache sudo)

```bash
php -S 127.0.0.1:8090 -t wc/public wc/public/router.php
php -S 127.0.0.1:8091 -t wa/public wa/public/router.php
php -S 127.0.0.1:8092 -t pw/public pw/public/router.php
```

## Uploads

Hero images are stored under `wc/uploads/hero/` with generated filenames. MySQL stores only the relative path. Soft delete **retains** files so restore works.

```bash
chmod -R ug+rwX wc/uploads wc/storage
# Ensure the web/PHP user can write these directories
```

`secure_file.php` validates MIME/extension/size/dimensions, blocks path traversal, and refuses non-images. Upload dirs disable PHP execution via `.htaccess`.

## API conventions

- JSON envelope: `{ "success": true|false, "message": "...", "data": ... }`
- Auth admin routes require Bearer session + CSRF on mutating methods
- Permission check on every Core admin API operation
- `GET` = read only; creates/updates/deletes/restores use `POST`
- Soft delete only — no permanent-delete endpoint or UI button
- Public endpoints: `/api/public/menus`, `/api/public/pages`, `/api/public/page`, `/api/public/hero`

## Soft delete & maintenance

Routine deletes set `deleted_at` / `deleted_by`. Association rows (`menu_pages`, `role_permissions`, `hero_pages`) are soft-deleted too. Restore is available from module screens and Trash.

**Backups:** use `mysqldump` / managed snapshots for physical retention. Do not add hard-delete UI. Periodic DBA cleanup of long-soft-deleted rows (if ever needed) is an offline maintenance task outside the application.

## Verification commands

```bash
# Syntax check
find wc wa pw -name '*.php' -print0 | xargs -0 -n1 php -l

# No direct DB usage outside Core
rg -n "new PDO|mysqli_|mysql_connect|pdo_mysql" wa pw || true

# Health
curl -s http://127.0.0.1:8090/health

# Login + permission denial smoke (replace password)
TOKEN=$(curl -s -X POST http://127.0.0.1:8090/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"superadmin@example.com","password":"YOUR_PASSWORD"}' | php -r 'echo json_decode(stream_get_contents(STDIN))->data->token;')
curl -s http://127.0.0.1:8090/api/users -H "Authorization: Bearer $TOKEN"
curl -s http://127.0.0.1:8090/api/users   # expect 401
```

## Sample Hero data

After migrations, seed demo hero images and page assignments:

```bash
php wc/bin/seed_hero_samples.php
```

### Sample Services

```bash
php wc/bin/migrate.php   # includes 002_services + seeds
php wc/bin/seed_services_samples.php
```

Creates English sample services on Home (some with optional list items, one without). Public **Services** section appears only when the page has assigned active services.

## Module coverage (Phase 0 + Phase 1)

- Auth: login, logout, session, password change, CSRF, rate limiting
- Users, Roles, Permissions, Menus (website/admin), Pages (website/admin), Trash, Audit
- Hero + `hero_pages` with per-page Active/Featured/order, image upload, public hero API
- Services + `service_pages` + optional `service_items` under description; public Services section
- Public site reads menus, pages, heroes, and services from Core only; multiple heroes → slider
