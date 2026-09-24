# Deploying the EPIC website to Hostinger

This guide covers a standard Hostinger shared-hosting account (hPanel). The
whole site is plain PHP — there is **no Node.js build step**, so you only need
to upload files, create a database and run one command.

**Requirements:** PHP **8.3 or newer**, MySQL/MariaDB, and the PHP extensions
Hostinger enables by default (`pdo_mysql`, `mbstring`, `openssl`, `fileinfo`,
`gd`, `zip`, `xml`, `ctype`, `tokenizer`, `curl`).

Deploying to Railway instead? The container image and its settings are covered
in **[RAILWAY.md](RAILWAY.md)**.

The application has been tested end to end against MariaDB 10.11 with MySQL 8's
strict `sql_mode` (`ONLY_FULL_GROUP_BY`), in production mode with cached
configuration, routes and views.

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

### Option C — deploy from GitHub (hPanel → GIT)

1. hPanel → **Websites → your domain → Advanced → GIT**.
2. Copy the SSH key hPanel shows you, then add it to the repository on GitHub
   (**Settings → Deploy keys → Add deploy key**, read access is enough).
3. Repository: `git@github.com:gulbaztariq/Epic.git`, branch: the one you want
   to publish. Leave the install path empty to deploy into `public_html`.
4. Press **Create**, then **Deploy**. Use the same **Deploy** button to publish
   later changes.

This path still needs `vendor/` — see below — so it suits accounts that have
SSH. Combine it with Option A's `.htaccess` (the repository includes one).

### Dependencies (`vendor/`)

- **With SSH** (Premium/Business plans): run
  `composer install --no-dev --optimize-autoloader` in the project folder.
- **Without SSH:** use the ready-made bundle (`epic-website-with-dependencies.zip`),
  which already contains `vendor/`. Upload it to `public_html`, extract, and skip
  straight to step 3. Alternatively run the command above on your own computer and
  upload the generated `vendor/` folder — it is deliberately not in git.

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

Pick whichever matches your hosting. **If your plan has no SSH and no cron
jobs, use Option 1** — it needs nothing but a browser.

### Option 1 — install from your browser (no SSH, no cron)

1. In `.env`, set a long random secret:

   ```dotenv
   EPIC_INSTALL_TOKEN=put-a-long-random-string-here
   ```

2. Open `https://your-domain.com/install.php?token=put-a-long-random-string-here`
3. Press **Install the website** and wait a few seconds.

It creates the tables, loads the pages, menus and starter content, creates your
dashboard account and caches the configuration — then **deletes itself**. Without
the secret the page refuses to do anything, and if the secret is missing from
`.env` it suggests one for you.

If it reports a problem, fix what it names and press install again — it is safe
to re-run.

### Option 2 — from the command line (SSH)

```bash
cd ~/domains/your-domain.com/public_html
php artisan epic:install --optimize
```

Check `php -v` first: the site needs PHP 8.3 or newer.

### Option 3 — a one-off cron job

hPanel → **Advanced → Cron Jobs**, set to every minute:

```
/usr/bin/php /home/u123456789/domains/your-domain.com/public_html/artisan epic:install --optimize --no-interaction
```

Wait for it to fire, confirm the site loads, then delete the cron job.

## 5. Keep the scheduled work running

Two jobs run in the background: visitor locations are looked up every 15
minutes, and expired visitor records are cleared out daily.

### If your plan has cron jobs

Add one entry, every minute — this is the most reliable option:

```
cd /home/u123456789/domains/your-domain.com/public_html && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

### If it does not

**Nothing to do — the site already handles it.** When cron is unavailable the
work runs quietly after a page has been served to a visitor, at most once every
five minutes, and never in a way a visitor waits for. You can see when each job
last ran, and run them on demand, under **Dashboard → Housekeeping**.

That setting lives in **Site settings → Analytics → "Run scheduled tasks on page
visits"**. It is on by default and safe to leave on even if you later add a cron
job, because each job records when it last ran.

The only difference on a very quiet site is timing: locations are resolved the
next time somebody visits, rather than on the quarter hour.

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

If you edit `.env`, the cached configuration must be rebuilt or the old values
stay live. With SSH run `php artisan optimize:clear`. Without SSH, use
**Dashboard → Housekeeping → Refresh caches**, which does exactly the same
thing.

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
| No SSH and no cron on my plan | Use the browser installer in step 4, Option 1. Scheduled work then runs automatically on page visits — see step 5. |
| Changed `.env` but nothing changed | The configuration is cached. **Dashboard → Housekeeping → Refresh caches**, or `php artisan optimize:clear`. |
| `install.php` says "Installer locked" | `EPIC_INSTALL_TOKEN` is empty in `.env`. The page suggests a secret — paste it in, save, reload. |
| `install.php` returns 404 | It has already installed the site and removed itself. Sign in at `/admin`. |
| Visitor countries stay empty | The cron job in step 5 is missing, or outbound HTTPS is blocked. Press **Resolve locations** on the analytics screen to test it. |
| Visitor counts look wrong by a few hours | `APP_TIMEZONE` is not set to your local zone. Set it, then run `php artisan optimize:clear`. |
| 404 on every page except the home page | `mod_rewrite` / `.htaccess` is not being read. Confirm `public/.htaccess` was uploaded (hidden files must be visible in File Manager). |
