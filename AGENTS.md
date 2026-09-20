# EPIC website — notes for developers and AI assistants

Laravel 13 / PHP 8.3+. Public website plus a super-admin dashboard at `/admin`.
Read `README.md` for the feature map and `DEPLOYMENT.md` for Hostinger.

## Ground rules

- **No build step.** The theme is hand-written CSS in `public/css/`. Do not add
  Vite, Tailwind, npm or any asset pipeline — the site is deployed to shared
  hosting by uploading files.
- **No `storage:link`.** Uploads go to `public/uploads/` through
  `App\Services\MediaService`. Always use that service so files are tracked in
  `media_files` and removed with their record.
- **Everything on the page must be editable.** New front-end content belongs in
  the database (a page field, a `page_sections` block, a `list_items` group or a
  dedicated model) and must be reachable from the dashboard.
- **Icons** come from `App\Support\Icons` via the `icon()` helper. Add new ones
  to that class rather than pulling in an icon font.
- **Never cache Eloquent models.** Laravel restricts which classes can be
  unserialized from the cache, so caching models throws
  `__PHP_Incomplete_Class` errors. Cache arrays and scalars only.
- **Do not name a model method after one of its columns** (for example
  `ListItem::group()` clashed with the `group` column and broke attribute
  access). Use `inGroup()`, `scopeX()` or similar.

## Adding a managed content type

1. Migration + model (use `App\Models\Concerns\HasSlug` when it needs a slug).
2. A controller in `app/Http/Controllers/Admin/Resources/` extending
   `ResourceController`, declaring `fields()` and `columns()`.
3. Register it in the `$resources` array in `routes/web.php`.
4. Add a sidebar link in `resources/views/admin/partials/sidebar.blade.php`.
5. Render it on the public side and cover it with a test.

Field types available to `fields()`: `text`, `textarea`, `richtext`, `select`,
`icon`, `checkbox`, `image`, `file`, `date`, `datetime`, `number`, `email`,
`url`, `password`, and `section` for a divider.

## Conventions

- Slug-based models resolve by slug in dashboard URLs too (`getRouteKeyName`).
- Public copy is seeded once in `database/seeders/` and never overwritten on a
  re-seed, so editor changes survive `php artisan db:seed`.
- Settings are read with `setting('key')`, written through
  `App\Models\Setting::put()` and defined in
  `App\Http\Controllers\Admin\SettingController::schema()`.
- Run `php artisan test` before committing; the suite covers every public route,
  every dashboard screen, CRUD with uploads, form submissions and permissions.
