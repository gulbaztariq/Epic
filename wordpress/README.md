# EPIC on WordPress

The same website as the Laravel app, run on WordPress, with **Rank Math SEO** for search
engine optimisation. Design, content and addresses are identical: the stylesheet and
script are the Laravel ones copied across unchanged, and every public page was compared
against the original at desktop and phone width (0% pixel difference, same text).

```
plugins/epic-core/   content types, edit screens, forms, visitor counter, SEO glue, importer
themes/epic/         the design: templates, CSS, script, images
tools/               export-laravel.php, which reads the Laravel database into export.json
starter/             export.json of a fresh install, for a site with no Laravel data to import
scripts/             migrate-to-wordpress.sh, the SSH migration for Hostinger
tests/               browser and smoke tests (see tests/README.md)
```

## Publishing to epic.org.pk

Run this **on the server**, over SSH, because that is where the Laravel database and
uploads are. Nothing here needs a password on the command line.

```bash
ssh -p 65002 <user>@<host>
curl -fsSL https://raw.githubusercontent.com/gulbaztariq/Epic/claude/epic-ritchie-o47wjd/wordpress/scripts/migrate-to-wordpress.sh -o migrate-to-wordpress.sh

bash migrate-to-wordpress.sh              # report only: looks at the server, changes nothing
DEPLOY=yes bash migrate-to-wordpress.sh   # does it
```

What `DEPLOY=yes` does, in order:

1. Finds the Laravel site and the folder `epic.org.pk` is served from.
2. Exports every page, picture, setting, menu, subscriber and message to
   `~/epic-wp-migration-<date>/export`.
3. Backs the Laravel database up (`mysqldump`, checked) into the same folder.
4. Downloads WordPress, Rank Math and WP-CLI (checksummed), and the EPIC theme and plugin.
5. Builds the new site in a **staging folder** next to the live one: installs WordPress,
   imports the content, configures Rank Math. The live site is untouched meanwhile.
6. Swaps the folders (two `mv` commands) and tests the new site over HTTP.
7. If any test fails it swaps straight back. Otherwise it prints the new admin sign-in.

Nothing is deleted. The old site is kept whole in `<folder>.laravel-<date>`, and the Laravel
database is never modified: WordPress uses its own tables (prefix `epicwp_`) in the same
database, so nothing has to be created in hPanel.

To put the old site back at any time:

```bash
bash migrate-to-wordpress.sh rollback ~/epic-wp-migration-<date>
```

Settings are environment variables listed at the top of the script (`SITE`, `DOCROOT`,
`DOMAIN`, `ADMIN_EMAIL`, `PHP_BIN`, `REF` …). The script needs PHP 8.1 or newer on the
command line, `curl`, `tar`, `unzip` and outbound HTTPS to wordpress.org, github.com and
downloads.wordpress.org; it checks each and stops before changing anything if one is missing.

### After the swap

- Sign in at `https://epic.org.pk/wp-admin` as `epicadmin` with the password the script
  printed once. Change it under Users.
- **Rank Math:** its setup wizard asks for a Rank Math account to link Google Search Console.
  The site is already configured and the account step is skipped, so SEO works without it.
  Connect an account later if you want the Search Console panels.
- Submit `https://epic.org.pk/sitemap_index.xml` in Google Search Console. Old addresses
  (`/p/<slug>`, `/sitemap.xml`, the former "Board of Governance" page) redirect with a 301.
- The Laravel visitor dashboards are replaced by **EPIC → Visitors**, a simpler report. The
  public counter keeps its totals: the Laravel figures are carried over as an offset.

## Content in the WordPress dashboard

Everything editable in the Laravel dashboard is editable here, under the **EPIC** menu:

| Laravel dashboard | WordPress |
| --- | --- |
| Pages, Page sections | Pages (built-in pages have a read-only address), Page sections |
| Publications, Events, Projects, Blogs & press, Careers | their own entries under **EPIC**, same fields |
| Team & councils, Partners, Chapters, Focus areas, Data & insights, Content lists | the same, under **EPIC** |
| Podcast, Videos, Photo gallery | the same, under **EPIC** |
| Messages, Volunteers, Subscribers | under **EPIC**; subscribers export to CSV |
| Navigation menus | Appearance → Menus (header and footer locations) |
| Site settings | **EPIC → Settings** (logos, colours, contact, social, footer, analytics snippets) |
| Per-picture fit, focus point, zoom | the same picture panel on every image field |

The classic editor is used for EPIC content so the markup matches the original site.

## Developing

Plugin and theme target PHP 8.0+ and WordPress 6.0+. There is no build step.

```bash
bash wordpress/tests/smoke.sh              # throw-away site, every page, Rank Math output
```

Browser tests against a running site are described in [`tests/README.md`](tests/README.md).

Rules that keep it working (the Laravel ones in `CLAUDE.md` still apply to the Laravel app):

- **`Epic_Schema` is the single source of truth** for content types, fields, settings and
  built-in pages. Add a field there and the edit screen, importer and templates see it.
- **Look built-in pages up by key** (`Epic_Data::page('home')`, meta `_epic_key`), never by
  slug or title; editors may change both. The importer, redirects and template routing use keys.
- **Never name a variable `$page`, `$more`, `$term`, `$post` or other WordPress globals in a
  theme template.** WordPress includes templates at global scope, so assigning to them
  overwrites the globals. Use an `$epic_` prefix.
- **Every uploaded picture in a frame gets `epic_pic_style($image)`**, as in Laravel; add new
  frames to the shared rule in `assets/css/site.css`.
- **Do not edit `site.css` and `site.js` to fit WordPress.** They are the Laravel files;
  WordPress-only rules go in `wordpress.css`. That is what keeps the two sites identical.
- **Degrade before the importer has run.** Code that reads a new meta key or option must
  fall back, not fail, so an update can be uploaded first and imported afterwards.
- **Cache arrays and scalars, never `Epic_Item` or `WP_Post` objects.**
- **No literal `<?xml` in a PHP file**: on a host with `short_open_tag` on it is read as PHP.
- **Never put credentials in this folder.** The scripts read passwords from the environment
  or from the Laravel `.env`, and write `wp-config.php` through PHP, not a command line.

### Importing again

The importer is idempotent (rows are matched on `_epic_source`), so a fresh export can be
loaded over an existing site without duplicates:

```bash
php wordpress/tools/export-laravel.php --app=/path/to/laravel --out=/tmp/export
wp epic import /tmp/export --dry-run      # what it would create and update
wp epic import /tmp/export
wp epic status
```
