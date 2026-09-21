# EPIC — Economic Policy and Innovation Centre

The public website and super-admin dashboard for EPIC, built with **Laravel 13**
and **PHP 8.3+**.

Everything visible on the website — page headings, hero text, focus areas,
publications, events, projects, people, partners, media, menus, contact details,
logos and images — is stored in the database and editable from the dashboard at
`/admin`. There is no build step: the theme is hand-written CSS, so the project
deploys to shared hosting by uploading files.

---

## Quick start (local)

```bash
composer install
cp .env.example .env
php artisan epic:install      # key, database, starter content, upload folders
php artisan serve
```

Then open:

- Website — <http://localhost:8000>
- Dashboard — <http://localhost:8000/admin>

**Default super admin** (change the password after the first sign-in):

```
Email:    admin@epic.org.pk
Password: EpicAdmin@2025
```

Set `EPIC_ADMIN_EMAIL` / `EPIC_ADMIN_PASSWORD` in `.env` before running
`epic:install` to create the account with your own credentials instead.

Run the test suite with `php artisan test`.

## Deploying

See **[DEPLOYMENT.md](DEPLOYMENT.md)** for step-by-step Hostinger instructions.

Neither SSH nor cron is required:

- **Installing** — set `EPIC_INSTALL_TOKEN` in `.env` and open
  `/install.php?token=…` in a browser. It creates the tables, loads the content,
  creates the admin account, caches the config, then deletes itself. It refuses
  to run without that secret.
- **Scheduled work** — when cron is unavailable, due jobs run after a page has
  been served (at most one check every five minutes, always after the response).
  **Dashboard → Housekeeping** shows when each last ran and can run them on
  demand, and also rebuilds the caches after an `.env` change.

---

## The website

| Section | Pages |
| --- | --- |
| Who We Are | About Us · Vision & Mission · EPIC Principles · Our Strengths · EPIC Team · Board of Governance · Advisory Council |
| What We Do | Themes of EPIC Work · Projects (+ detail pages) · International Chapters |
| Events | Upcoming and past events (+ detail pages) |
| Partnerships & MoUs | Partnerships · MoUs · Memberships |
| Publications | Our Collection · Journal (HEC-recognized) · E-Newsletter · Blogs & Articles |
| Get Involved | Careers (+ detail pages) · Volunteer · Subscribe · Contact |
| Media | Press Releases · Podcast · YouTube · Gallery |
| Other | Search, sitemap.xml, Privacy Policy, Terms of Use, 403/404/500 pages |

Every page is responsive, uses the EPIC logo palette, and degrades gracefully
when a section has no content yet.

## The dashboard

| Dashboard section | What it controls |
| --- | --- |
| **Pages** | Hero heading, eyebrow, hero image, intro, body copy, quote, buttons and SEO for each page |
| **Page sections** | Extra blocks on any page (text, checklists, icon cards, image + text, quote, accordion, call to action) and the home page section headings |
| **Navigation menus** | Header dropdowns, footer columns and the footer legal links |
| **Focus areas** | The seven-icon strip on the home page |
| **Content lists** | Reusable lists: principles, strengths, themes, project types, event types, partnership areas, MoU scope, memberships, entrepreneurship pillars, hero highlights |
| **Data & insights** | The statistic tiles on the home page |
| **Publications** | Reports, briefs, papers, journal issues and newsletters, with cover image and PDF |
| **Events** | Dates, venue, format, registration link, image |
| **Projects** | Project pages with status, partners and timeline |
| **Blogs & press** | Blogs, articles and press releases |
| **Team & councils** | EPIC Team, Board of Governance and Advisory Council profiles |
| **Partners & MoUs** | Partner organisations, signed MoUs and memberships |
| **International chapters** | Country chapters |
| **Careers** | Vacancies, internships and fellowships |
| **Podcast · Videos · Photo gallery** | Media library content |
| **Media library** | Every uploaded file, with copyable URLs |
| **Visitor overview** | Traffic reports for any date range: page views, visitors, sessions, first-time visits, countries, a day-by-day (or hour-by-hour) chart, most-read pages, countries, cities, traffic sources, devices, browsers and operating systems |
| **Visitor log** | Every recorded page view, filterable by date, country, device and page |
| **Messages · Volunteers · Subscribers** | Form submissions, with CSV export for subscribers |
| **Housekeeping** | For hosting without SSH: run the scheduled jobs on demand and rebuild the caches after editing `.env` (super admins and administrators) |
| **Site settings** | Logos, favicon, site name, contact details, social links, footer text, header button, analytics snippets |
| **Admin users** | Dashboard accounts and roles |

Roles: **Super Admin** and **Administrator** can manage everything including
settings and users; **Editor** can manage content only.

---

## How it is put together

```
app/
  Http/Controllers/            Public site controllers
  Http/Controllers/Admin/      Dashboard: auth, settings, media, inbox
  Http/Controllers/Admin/Resources/   One small class per managed content type
  Models/                      Eloquent models
  Services/MediaService.php    Uploads: storing, resizing, deleting
  Support/Icons.php            Inline SVG icon set (no icon font)
  Support/helpers.php          setting(), epic_image(), rich(), icon() …
resources/views/
  layouts/site.blade.php       Public layout
  layouts/admin.blade.php      Dashboard layout
  site/                        Public pages
  admin/resource/              The generic list + form screens
  components/                  Reusable cards and blocks
public/css/site.css            Website theme
public/css/admin.css           Dashboard theme
```

### The CRUD engine

Each dashboard content type is a short class extending
`App\Http\Controllers\Admin\ResourceController`. It declares its fields and
table columns; listing, forms, validation, image uploads, replacement and
deletion are handled for it. Adding a new managed content type means adding a
model, a migration and roughly forty lines of controller.

### Uploads

Files are written to `public/uploads/<folder>/` and served directly, so **no
`storage:link` symlink is required** — which is what makes this work smoothly on
shared hosting. Images wider than 2000px are resized automatically. Replacing or
deleting a record deletes the old file.

### Visitor analytics

Every page view of the public website is recorded in the `visits` table by
`TrackVisitors` middleware. The work happens in the middleware's `terminate()`
method — after the response has already been sent — so pages are not slowed
down. Dashboard pages, `sitemap.xml`, `/up`, asset paths and form submissions
are never counted; crawlers are recorded but flagged, and left out of the
reports unless you tick **Include bots**.

Each visit stores the page, title, referrer, device type, browser, operating
system, language, an approximate location, and a hashed visitor and session key
(so the same person can be recognised without storing anything identifying).
**IP addresses are shortened** — `203.0.113.42` becomes `203.0.113.0` — unless
full addresses are switched on in **Site settings → Analytics**.

Locations are resolved out of band: an unknown address is queued in
`ip_locations` and looked up by `php artisan epic:resolve-visitor-locations`
(scheduled every 15 minutes) using [ipwho.is](https://ipwho.is), which is free
and needs no API key. Each address is looked up once and reused, and a
`CF-IPCountry` header from Cloudflare is used immediately when present. If the
server has no cron job, the overview screen has a **Resolve locations** button
that does the same thing on demand. To use a different provider, change
`GeoLocator::ENDPOINT` and `mapResponse()`.

Old records are deleted by `php artisan epic:prune-visits` according to the
retention setting (365 days by default; `0` keeps everything). Both commands run
from Laravel's scheduler — see `routes/console.php` — driven either by the single
cron entry described in DEPLOYMENT.md or, where cron is unavailable, by
`App\Services\WebScheduler` after a page response.

Reports and "today" follow `APP_TIMEZONE`, so set it to your local zone (for
example `Asia/Karachi`) before the site goes live.

The public footer shows a visitor counter, which can be switched off, relabelled,
or set to show page views instead — all under **Site settings → Analytics**.

### Theme colours

The palette lives in the `:root` block at the top of `public/css/site.css` (and
mirrors it in `admin.css`):

```css
--navy: #13366e;   /* logo wordmark   */
--blue: #0f6fc0;   /* logo swoosh     */
--cyan: #00a8e8;
--green: #41a62a;  /* logo leaf       */
--lime: #7ac943;
```

Typography: *Playfair Display* for headings, *Inter* for body text and
*Montserrat* for the brand lockup, loaded from Google Fonts with system
fallbacks.

### Starter content

`php artisan epic:install` loads the real EPIC copy (about, vision, mission,
principles, strengths, themes, project and event types, partnership areas,
memberships and the international chapters) plus a handful of clearly-marked
sample publications, events, projects and posts so the site looks complete from
day one. Edit or delete the samples from the dashboard.
