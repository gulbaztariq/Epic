# Deploying the EPIC website to Railway

The site is live at **https://epic-web-production.up.railway.app** (dashboard at
`/admin`) in the Railway project **epic-website**.

Railway runs the site from the `Dockerfile` in this repository rather than from
uploaded files, so the deployment is reproducible and nothing here is specific
to Railway — the same image runs on any container host. For shared hosting see
[DEPLOYMENT.md](DEPLOYMENT.md) instead.

---

## What the project contains

| Service | What it is |
| --- | --- |
| **epic-web** | This repository, built from `Dockerfile`: Apache + PHP 8.3 serving `public/`. A 500 MB volume is mounted at `/var/www/html/public/uploads` so uploaded images survive a redeploy. |
| **MySQL** | Railway's MySQL template, database `epic`, with its own volume at `/var/lib/mysql`. |

`epic-web` reads the database over Railway's private network through variable
references (`${{MySQL.MYSQLHOST}}` and friends), so the credentials are never
copied and stay correct if the database is replaced.

**Pre-deploy command:** `php artisan epic:install --optimize --no-interaction`.
It migrates, seeds the starter content the first time only, and creates the
super admin. It is safe to run on every deploy — a second run reports
`Starter content … skipped — content already exists`.

**Healthcheck:** `/up`. Railway will not switch traffic to a new container until
it answers 200, so a broken deploy leaves the previous one serving.

---

## Environment variables

Set on the `epic-web` service. The ones that matter:

```
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:…              # generate with `php artisan key:generate --show`
APP_URL=https://epic-web-production.up.railway.app
APP_TIMEZONE=Asia/Karachi

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=database        # a container's local disk is not durable
CACHE_STORE=database
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr             # so Railway collects the logs

EPIC_ADMIN_NAME=…
EPIC_ADMIN_EMAIL=…
EPIC_ADMIN_PASSWORD=…          # only used to create the account; change it on first sign-in
```

`APP_URL` must match the domain, because the sitemap and any emailed link are
built from it.

---

## Deploying a change

Push to the branch the service is connected to. **Automatic deploy on push is
currently off**, because the Railway GitHub App is not installed on the
repository. Either install it at
<https://github.com/apps/railway-app/installations/new> and enable auto-deploy
in the service settings, or press **Deploy** in the Railway dashboard after
pushing.

Redeploying runs the pre-deploy command again, so migrations are applied
automatically.

---

## Notes that cost time to rediscover

- **The pre-deploy command runs in a throwaway container.** Anything it writes
  to disk — including the config, route and view caches — is discarded before
  the serving container starts. `docker/entrypoint.sh` warms those caches in the
  container that actually serves traffic. Anything that must persist has to go
  to the database or the volume.
- **The volume starts empty and shadows whatever the image had at that path.**
  The entrypoint creates and chowns `public/uploads` at start-up for that
  reason.
- **`trustProxies` is required.** Railway terminates TLS at its edge, so without
  it every visitor is recorded as the load balancer, unique-visitor counts and
  locations become meaningless, and generated URLs come out as `http`.
- **Only one MPM may be loaded or Apache will not start.** The base image
  disables `mpm_event` by deleting its symlink, and that deletion does not
  survive into the running container, so the entrypoint removes any MPM but
  prefork before starting Apache. `apache2ctl -t` does *not* catch this — it
  reports "Syntax OK" for a config that cannot start.
- **`short_open_tag` is on**, because the official PHP images ship no `php.ini`.
  Never write `<?` in a Blade template (see `CLAUDE.md`). The image sets
  `short_open_tag=Off` as well, but the code should not depend on that.

---

## Logs and debugging

Laravel logs to stderr, so a stack trace appears in the service's deploy logs in
the Railway dashboard — no log file to fetch. The build logs show each
`Dockerfile` step, which is where an image problem will surface.

`/up` is excluded from visitor tracking, as are `admin*`, `sitemap.xml` and
static assets, so the healthcheck does not pollute the analytics.
