<?php

/*
|--------------------------------------------------------------------------
| EPIC — one-time browser installer
|--------------------------------------------------------------------------
|
| For hosting without SSH or cron. It creates the database tables, loads the
| website content and the super admin account, then removes itself.
|
| To use it:
|   1. Put a long random secret in .env:  EPIC_INSTALL_TOKEN=some-long-secret
|   2. Open https://your-domain.com/install.php?token=some-long-secret
|   3. Press "Install the website".
|
| Without that secret the page does nothing at all.
|
*/

declare(strict_types=1);
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store, max-age=0');
header('Referrer-Policy: no-referrer');

set_time_limit(300);
ignore_user_abort(true);

$root = dirname(__DIR__);

if (! is_file($root.'/vendor/autoload.php')) {
    http_response_code(500);
    exit('The vendor folder is missing. Upload the bundle that includes dependencies, or run "composer install".');
}

require $root.'/vendor/autoload.php';

/** @var Application $app */
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$expected = (string) config('epic.install_token');
$supplied = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$authorised = $expected !== '' && hash_equals($expected, $supplied);

/**
 * Render the page and stop.
 */
function page(string $heading, string $body, string $tone = 'info', ?string $footer = null): never
{
    $colours = [
        'info' => '#0f6fc0',
        'ok' => '#41a62a',
        'error' => '#d64545',
    ];
    $accent = $colours[$tone] ?? $colours['info'];

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
        .'<meta name="viewport" content="width=device-width, initial-scale=1">'
        .'<meta name="robots" content="noindex, nofollow">'
        .'<title>EPIC — Install</title><style>'
        .'*{box-sizing:border-box}'
        .'body{margin:0;min-height:100vh;display:grid;place-items:center;padding:28px 18px;'
        .'font:15px/1.65 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;'
        .'color:#4a5871;background:linear-gradient(135deg,#0d2450,#13366e 55%,#0c63ad)}'
        .'.card{width:100%;max-width:620px;background:#fff;border-radius:14px;padding:34px;'
        .'box-shadow:0 24px 60px rgba(6,20,45,.35)}'
        .'h1{margin:0 0 6px;font-size:1.35rem;color:#13203a}'
        .'h1 span{display:block;height:4px;width:54px;border-radius:2px;background:'.$accent.';margin-top:12px}'
        .'p{margin:0 0 14px}code{background:#f2f6fb;padding:2px 6px;border-radius:4px;font-size:.88em;word-break:break-all}'
        .'pre{background:#0d2450;color:#e8f1fb;padding:16px;border-radius:8px;overflow:auto;font-size:.8rem;line-height:1.5;max-height:340px}'
        .'ol,ul{padding-left:1.2rem}li{margin-bottom:7px}'
        .'button{font:inherit;font-weight:600;cursor:pointer;border:0;border-radius:7px;padding:13px 24px;'
        .'background:#13366e;color:#fff}button:hover{background:#1b4587}'
        .'a{color:#0f6fc0}.muted{color:#7d8da4;font-size:.86rem}'
        .'</style></head><body><div class="card"><h1>'.$heading.'<span></span></h1>'.$body
        .($footer ? '<p class="muted" style="margin-top:22px">'.$footer.'</p>' : '')
        .'</div></body></html>';

    exit;
}

/* ---------------------------------------------------------------- Guards */

if ($expected === '') {
    page(
        'Installer locked',
        '<p>To use this installer, open your <code>.env</code> file and set a secret:</p>'
        .'<pre>EPIC_INSTALL_TOKEN='.bin2hex(random_bytes(16)).'</pre>'
        .'<p>Save the file, then come back to:</p>'
        .'<pre>'.htmlspecialchars(($_SERVER['HTTP_HOST'] ?? 'your-domain.com').'/install.php?token=YOUR-SECRET', ENT_QUOTES).'</pre>'
        .'<p class="muted">A secret has been suggested above — you can use it as-is.</p>',
        'error'
    );
}

if (! $authorised) {
    http_response_code(403);
    page('Not authorised', '<p>Add <code>?token=</code> followed by the secret from your <code>.env</code> file.</p>', 'error');
}

/* ------------------------------------------------------- Already set up? */

$installed = false;

try {
    $installed = Schema::hasTable('users')
        && User::query()->exists();
} catch (Throwable) {
    // No database connection yet — that is what we are here to fix.
}

$force = ($_POST['force'] ?? '') === 'yes';

/* --------------------------------------------------------------- Install */

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if ($installed && ! $force) {
        page('Already installed', '<p>The database already contains a dashboard account, so nothing was changed.</p>', 'info');
    }

    try {
        Artisan::call('epic:install', ['--optimize' => true]);
        $output = trim(Artisan::output());
    } catch (Throwable $e) {
        page(
            'Installation failed',
            '<p>Nothing is broken — fix the problem below and press install again.</p>'
            .'<pre>'.htmlspecialchars($e->getMessage(), ENT_QUOTES).'</pre>'
            .'<p>The usual causes are wrong database details in <code>.env</code>, or '
            .'<code>storage</code> and <code>bootstrap/cache</code> not being writable (set both to 775).</p>',
            'error'
        );
    }

    $removed = @unlink(__FILE__);
    $adminUrl = rtrim((string) config('app.url'), '/').'/admin';

    page(
        'Your website is ready',
        '<pre>'.htmlspecialchars($output, ENT_QUOTES).'</pre>'
        .'<p><strong>Next:</strong> sign in at <a href="'.htmlspecialchars($adminUrl, ENT_QUOTES).'">'
        .htmlspecialchars($adminUrl, ENT_QUOTES).'</a> and change your password under <em>My profile</em>.</p>'
        .($removed
            ? '<p>This installer has deleted itself.</p>'
            : '<p><strong>Please delete <code>public/install.php</code> now</strong> — it could not remove itself.</p>')
        .'<p>Scheduled work (visitor locations, tidying old records) runs automatically on page visits '
        .'when no cron job is available. You can also trigger it from the dashboard.</p>',
        'ok'
    );
}

/* ------------------------------------------------------------- The form */

$token = htmlspecialchars($supplied, ENT_QUOTES);
$database = htmlspecialchars((string) config('database.connections.'.config('database.default').'.database'), ENT_QUOTES);

$intro = $installed
    ? '<p>This website is <strong>already installed</strong>. Running the installer again will re-apply any '
        .'missing database changes and top up missing content. Existing pages and settings are left alone.</p>'
    : '<p>This will create the database tables, load the website pages and menus, and create your '
        .'dashboard account. It takes a few seconds.</p>';

page(
    $installed ? 'Re-run the installer?' : 'Install the EPIC website',
    $intro
    .'<ul>'
    .'<li>Database: <code>'.($database !== '' ? $database : 'not configured').'</code></li>'
    .'<li>Connection: <code>'.htmlspecialchars((string) config('database.default'), ENT_QUOTES).'</code></li>'
    .'</ul>'
    .'<form method="post">'
    .'<input type="hidden" name="token" value="'.$token.'">'
    .($installed ? '<input type="hidden" name="force" value="yes">' : '')
    .'<button type="submit">'.($installed ? 'Run it again' : 'Install the website').'</button>'
    .'</form>',
    'info',
    'This page is protected by the secret in your .env file and removes itself once the site is installed.'
);
