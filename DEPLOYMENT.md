# Deploying the EPIC website to Hostinger

This guide covers a standard Hostinger shared-hosting account (hPanel). The
whole site is plain PHP — there is **no Node.js build step**, so you only need
to upload files, create a database and run one command.

**Requirements:** PHP **8.3 or newer**, MySQL, and the PHP extensions Hostinger
enables by default (`pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`, `zip`,
`xml`, `ctype`, `tokenizer`, `curl`).

---

## 1. Create the database

1. hPanel → **Databases → Management**.
2. Create a new MySQL database and user. Use a strong password.
3. Write down the four values — you need them in step 3:
   - database name (e.g. `u123456789_epic`)
   - username (e.g. `u123456789_epic`)
   - password
   - host (`localhost` on Hostinger)

## 2. Upload the project

### Option A — everything inside `public_html` (works on every plan)

1. hPanel → **Files → File Manager**, open `public_html`.
2. Upload the project (a ZIP of this repository is easiest) and extract it so
   that `public_html` directly contains `app/`, `bootstrap/`, `config/`,
   `public/`, `.htaccess`, `artisan`, and so on.
3. The included root `.htaccess` forwards every request into `public/` and
   blocks direct access to application folders. Nothing else to configure.

### Option B — app outside the web root (cleaner, if your plan allows it)

1. Upload the project to `~/domains/your-domain.com/epic`.
2. hPanel → **Websites → your domain → Change website root directory** and set
   it to `domains/your-domain.com/epic/public`.
3. You can delete the root `.htaccess` in this setup.

### Dependencies (`vendor/`)

- **With SSH** (Premium/Business plans): run
  `composer install --no-dev --optimize-autoloader` in the project folder.
- **Without SSH:** run that command on your own computer first and upload the
  generated `vendor/` folder with the rest of the files. It is not in git.

## 3. Create the `.env` file

Copy `.env.example` to `.env` (File Manager → right-click → Copy / Rename) and
set it to your production values:

```dotenv
APP_NAME="EPIC"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_TIMEZONE=Asia/Karachi

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_epic
DB_USERNAME=u123456789_epic
DB_PASSWORD=your-database-password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Optional — lets the site email you when a form is submitted.
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=info@your-domain.com
MAIL_PASSWORD=your-mailbox-password
MAIL_FROM_ADDRESS="info@your-domain.com"
MAIL_FROM_NAME="EPIC"

# The first dashboard account that will be created.
EPIC_ADMIN_NAME="EPIC Super Admin"
EPIC_ADMIN_EMAIL=admin@your-domain.com
EPIC_ADMIN_PASSWORD=change-this-to-something-strong
```

> Leave `APP_KEY` empty — the install command fills it in. `APP_DEBUG` **must**
> be `false` in production.

## 4. Install the site

Run this once, from the project folder:

```bash
php artisan epic:install --optimize
```

It generates the application key, creates every table, loads the pages, menus
and starter content, creates the upload folders and caches the configuration.

**No SSH access?** Use hPanel → **Advanced → Cron Jobs** to run it once:

```
/usr/bin/php /home/u123456789/domains/your-domain.com/public_html/artisan epic:install --optimize --no-interaction
```

Set it to run every minute, wait for it to fire, then delete the cron job.

## 5. Add the cron job

The visitor statistics need one cron entry. hPanel → **Advanced → Cron Jobs**,
set it to run **every minute**:

```
cd /home/u123456789/domains/your-domain.com/public_html && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

That single entry runs everything on a schedule: visitor locations are looked up
every 15 minutes and records older than the retention period are cleared out
each night. Without it the site still counts visitors — you just have to press
**Resolve locations** on the analytics screen to fill in countries and cities.

## 6. Permissions

`storage/` and `bootstrap/cache/` must be writable, and `public/uploads/` must
be writable so the dashboard can save images:

```bash
chmod -R 775 storage bootstrap/cache public/uploads
```

In File Manager: right-click each folder → **Permissions** → `775`, tick
"apply to subdirectories".

## 7. Secure the site

1. hPanel → **Security → SSL** — install the free SSL certificate.
2. Turn on **Force HTTPS**.
3. Make sure `APP_URL` in `.env` starts with `https://`.
4. Sign in at `https://your-domain.com/admin` and change the super admin
   password immediately (**Dashboard → My profile**).

---

## Updating the site later

After uploading changed files, clear the caches:

```bash
php artisan optimize:clear
php artisan optimize          # re-cache for production
php artisan migrate --force   # only if the database changed
```

If you edit `.env`, always run `php artisan optimize:clear` afterwards —
otherwise the old cached configuration is still used.

## Backups

- **Database:** hPanel → Databases → phpMyAdmin → Export.
- **Uploaded images and documents:** back up the `public/uploads` folder.

Those two cover everything an editor has created; the rest is code. Visitor
statistics live in the database, so the database export covers them too.

---

## Troubleshooting

| Symptom | Fix |
| --- | --- |
| 500 error on every page | Check `storage/logs/laravel.log`. Usually a missing `APP_KEY`, wrong database credentials, or `storage/` not writable. |
| "No application encryption key" | Run `php artisan key:generate --force`. |
| Blank page / "Whoops" after an update | `php artisan optimize:clear` |
| Styles missing, site looks unstyled | The domain is pointing at the project root instead of `public/`. Confirm the root `.htaccess` was uploaded, or use Option B above. |
| Images upload but do not show | `public/uploads` is not writable (set `775`), or `APP_URL` does not match the real domain. |
| Login says credentials do not match | Reset the password: `php artisan tinker --execute="\App\Models\User::where('email','admin@your-domain.com')->update(['password'=>bcrypt('new-password')]);"` |
| Forms save but no email arrives | SMTP is not configured. Submissions are still safe in **Dashboard → Messages**. |
| Visitor countries stay empty | The cron job in step 5 is missing, or outbound HTTPS is blocked. Press **Resolve locations** on the analytics screen to test it. |
| Visitor counts look wrong by a few hours | `APP_TIMEZONE` is not set to your local zone. Set it, then run `php artisan optimize:clear`. |
| 404 on every page except the home page | `mod_rewrite` / `.htaccess` is not being read. Confirm `public/.htaccess` was uploaded (hidden files must be visible in File Manager). |
