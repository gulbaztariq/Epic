# Tests for the WordPress port

Two layers. Neither needs anything installed on the production server.

## `smoke.sh`: no browser

Builds a throw-away WordPress in a temp folder (EPIC theme and plugin linked in, starter
content imported, Rank Math active), serves it with PHP's built-in server and checks that:

- every page and every EPIC item answers 200 (container pages answer 301), with a heading
  and no PHP notice;
- the old addresses (`/p/<slug>`, `/who-we-are/board-of-governance`, `/sitemap.xml`, `/?s=`)
  redirect where they should, and an unknown address is a 404;
- Rank Math prints a title, description, canonical link, Open Graph and Twitter tags and
  structured data, serves `sitemap_index.xml`, and `robots.txt` names it.

```bash
DB_HOST=127.0.0.1 DB_USER=root DB_PASS= DB_NAME=epic_smoke bash wordpress/tests/smoke.sh
```

The database named `DB_NAME` is **emptied**; use a scratch one. `WP_SRC` and `RANKMATH_SRC`
point at unpacked copies to skip downloads; `WP_CLI` selects the WP-CLI command; `KEEP=1`
leaves the site running for inspection. CI runs this in the `wordpress-site` job.

## Browser tests (Playwright)

Run against a **throw-away** site (they submit forms, edit content and empty the visit log;
`guard.js` refuses anything that is not localhost or `*.test` unless
`EPIC_TESTS_MAY_WRITE=yes`).

```bash
npm install --no-save playwright pngjs pixelmatch   # not part of the site; tests only
export WP_BASE=http://127.0.0.1:8081 WP_CLI="wp --path=/path/to/site" \
       WP_ADMIN_USER=<user> WP_ADMIN_PASS=<password>

node wordpress/tests/e2e.js          # redirects, pagination, forms, counter, menus, lightbox
node wordpress/tests/admin-e2e.js    # edits content in the dashboard and checks the public page
node wordpress/tests/admin-check.js  # every dashboard screen loads without PHP errors or JS errors
```

### Visual comparison with the Laravel original

`compare.js` screenshots every public route on both sites and reports the pixel difference
and any text that differs. Start the Laravel app (`php artisan serve --port=8080`) and the
WordPress site, then:

```bash
LARAVEL_BASE=http://127.0.0.1:8080 WP_BASE=http://127.0.0.1:8081 node wordpress/tests/compare.js            # desktop
LARAVEL_BASE=http://127.0.0.1:8080 WP_BASE=http://127.0.0.1:8081 node wordpress/tests/compare.js --mobile   # phone
```

Both sites must hold the same content (import a fresh export into WordPress first), and
dates that depend on "now" in the seed data can differ by design. Screenshots and diffs go to
`tests/out/` (ignored by git); an optional first argument filters routes by name.
