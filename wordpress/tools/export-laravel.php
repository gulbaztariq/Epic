#!/usr/bin/env php
<?php
/**
 * Export everything the EPIC Laravel site holds — content, settings, menus,
 * picture choices, form submissions, visitor totals and every uploaded file —
 * into one folder that `wp epic import` can read.
 *
 *   php export-laravel.php --app=/path/to/laravel --out=/path/to/export
 *
 * It is standalone on purpose: no Laravel bootstrap, no Composer, just PDO. It
 * only ever READS the database and the uploads folder, so it is safe to run
 * against the live site while it keeps serving visitors.
 *
 * Options
 *   --app=DIR     the Laravel project (folder holding artisan). Default: .
 *   --out=DIR     where to write export.json and uploads/. Default: ./epic-export
 *   --no-files    skip copying public/uploads (the JSON is still written)
 *   --print-db-env  print the database settings as shell variable assignments and exit
 *                   (used by the deployment script to make its backup; nothing is exported)
 *
 * Exit status: 0 on success, 1 on any failure (with the reason on stderr).
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

const FORMAT = 1;

/** Tables to export, in the order the importer needs them. */
const TABLES = [
    'settings', 'media_files', 'pages', 'page_sections', 'menu_items', 'focus_areas',
    'list_items', 'stats', 'team_members', 'projects', 'chapters', 'partners', 'careers',
    'publications', 'posts', 'events', 'podcasts', 'videos', 'gallery_albums',
    'gallery_images', 'subscribers', 'contact_messages', 'volunteer_applications',
    'image_settings',
];

function fail(string $message)
{
    fwrite(STDERR, "export: {$message}\n");
    exit(1);
}

function say(string $message): void
{
    fwrite(STDOUT, $message."\n");
}

/* --------------------------------------------------------------- .env --- */

/** A deliberately small dotenv reader: KEY=value, quotes, comments, `export`. */
function read_env(string $file): array
{
    $values = [];

    if (! is_file($file)) {
        return $values;
    }

    foreach (file($file, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $line = trim($line);

        if ($line === '' || $line[0] === '#') {
            continue;
        }

        if (str_starts_with($line, 'export ')) {
            $line = trim(substr($line, 7));
        }

        if (! str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
            $quote = $value[0];
            $end = strpos($value, $quote, 1);
            $value = $end === false ? substr($value, 1) : substr($value, 1, $end - 1);
            if ($quote === '"') {
                $value = str_replace(['\\n', '\\"', '\\\\'], ["\n", '"', '\\'], $value);
            }
        } else {
            $value = trim(preg_replace('/\s+#.*$/', '', $value) ?? $value);
        }

        $values[$key] = $value;
    }

    return $values;
}

/**
 * How the running site reaches its database. The cached configuration wins when it
 * exists, because that is what the site really uses (it can differ from .env).
 *
 * @return array{driver:string,host:string,port:string,database:string,username:string,password:string,socket:string,timezone:string,url:string,source:string}
 */
function database_settings(string $app): array
{
    $env = read_env($app.'/.env');

    $cached = $app.'/bootstrap/cache/config.php';
    if (is_file($cached)) {
        $config = @include $cached;

        if (is_array($config) && isset($config['database']['default'])) {
            $name = $config['database']['default'];
            $c = $config['database']['connections'][$name] ?? [];

            return [
                'driver' => (string) ($c['driver'] ?? $name),
                'host' => (string) ($c['host'] ?? '127.0.0.1'),
                'port' => (string) ($c['port'] ?? '3306'),
                'database' => (string) ($c['database'] ?? ''),
                'username' => (string) ($c['username'] ?? ''),
                'password' => (string) ($c['password'] ?? ''),
                'socket' => (string) ($c['unix_socket'] ?? ''),
                'timezone' => (string) ($config['app']['timezone'] ?? 'UTC'),
                'url' => (string) ($config['app']['url'] ?? ''),
                'source' => 'bootstrap/cache/config.php',
            ];
        }
    }

    $get = static fn (string $key, string $default = '') => $env[$key] ?? getenv($key) ?: $default;

    return [
        'driver' => $get('DB_CONNECTION', 'mysql'),
        'host' => $get('DB_HOST', '127.0.0.1'),
        'port' => $get('DB_PORT', '3306'),
        'database' => $get('DB_DATABASE'),
        'username' => $get('DB_USERNAME'),
        'password' => $get('DB_PASSWORD'),
        'socket' => $get('DB_SOCKET'),
        'timezone' => $get('APP_TIMEZONE', 'UTC'),
        'url' => $get('APP_URL'),
        'source' => '.env',
    ];
}

function connect(array $db, string $app): PDO
{
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ];

    if ($db['driver'] === 'sqlite') {
        $name = $db['database'];
        $candidates = [$name, $app.'/database/'.$name, $app.'/'.$name, $app.'/database/database.sqlite'];

        foreach ($candidates as $path) {
            if ($path !== '' && is_file($path)) {
                return new PDO('sqlite:'.$path, null, null, $options);
            }
        }

        fail('The SQLite database file was not found (looked for '.implode(', ', array_filter($candidates)).').');
    }

    if (! in_array($db['driver'], ['mysql', 'mariadb'], true)) {
        fail("Unsupported database driver \"{$db['driver']}\" — the exporter reads MySQL, MariaDB and SQLite.");
    }

    $dsn = $db['socket'] !== ''
        ? "mysql:unix_socket={$db['socket']};dbname={$db['database']};charset=utf8mb4"
        : "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset=utf8mb4";

    return new PDO($dsn, $db['username'], $db['password'], $options + [PDO::ATTR_EMULATE_PREPARES => false]);
}

/* ------------------------------------------------------------- export --- */

/** The people who can sign in to the dashboard (never their passwords). */
function dashboard_admins(PDO $pdo): array
{
    try {
        return array_map(
            static fn ($u) => ['name' => (string) $u['name'], 'email' => (string) $u['email'], 'role' => (string) ($u['role'] ?? '')],
            $pdo->query("SELECT name, email, role FROM users WHERE role IN ('super_admin', 'admin', 'administrator') ORDER BY id")->fetchAll()
        );
    } catch (PDOException) {
        return [];
    }
}

/** Every row of a table, or [] when the table does not exist on this install. */
function dump_table(PDO $pdo, string $table): array
{
    try {
        return $pdo->query("SELECT * FROM {$table} ORDER BY id")->fetchAll();
    } catch (PDOException) {
        return [];
    }
}

/** Human visits only, matching what the site's own counter shows. */
function visit_summary(PDO $pdo): array
{
    $empty = ['page_views' => 0, 'visitors' => 0, 'since' => null, 'daily' => []];

    try {
        $row = $pdo->query(
            'SELECT COUNT(*) AS views, COUNT(DISTINCT visitor_key) AS visitors, MIN(visited_at) AS since '
            .'FROM visits WHERE is_bot = 0'
        )->fetch();

        $daily = $pdo->query(
            'SELECT DATE(visited_at) AS day, COUNT(*) AS views, COUNT(DISTINCT visitor_key) AS visitors '
            .'FROM visits WHERE is_bot = 0 GROUP BY DATE(visited_at) ORDER BY day'
        )->fetchAll();

        return [
            'page_views' => (int) $row['views'],
            'visitors' => (int) $row['visitors'],
            'since' => $row['since'] ?: null,
            'daily' => array_map(
                static fn ($d) => ['day' => $d['day'], 'views' => (int) $d['views'], 'visitors' => (int) $d['visitors']],
                $daily
            ),
        ];
    } catch (PDOException) {
        return $empty;
    }
}

/** Copy public/uploads into the export, returning a manifest of what was copied. */
function copy_uploads(string $app, string $out): array
{
    $source = $app.'/public/uploads';
    $files = [];

    if (! is_dir($source)) {
        return $files;
    }

    $base = realpath($source);
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS)
    );

    foreach ($iterator as $file) {
        if (! $file->isFile() || str_starts_with($file->getFilename(), '.')) {
            continue;
        }

        $relative = 'uploads/'.str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));
        $target = $out.'/'.$relative;

        if (! is_dir(dirname($target)) && ! mkdir(dirname($target), 0755, true) && ! is_dir(dirname($target))) {
            fail('Cannot create '.dirname($target));
        }

        if (! copy($file->getPathname(), $target)) {
            fail('Cannot copy '.$file->getPathname());
        }

        $files[] = ['path' => $relative, 'size' => $file->getSize()];
    }

    usort($files, static fn ($a, $b) => strcmp($a['path'], $b['path']));

    return $files;
}

/* --------------------------------------------------------------- main --- */

$options = getopt('', ['app::', 'out::', 'no-files', 'print-db-env', 'help']);

if (isset($options['help'])) {
    $source = (string) file_get_contents(__FILE__);
    preg_match('~/\*\*(.*?)\*/~s', $source, $doc);
    echo trim(preg_replace('~^\s*\* ?~m', '', $doc[1] ?? '')), "\n";
    exit(0);
}

$app = rtrim((string) ($options['app'] ?? getcwd()), '/');
$out = rtrim((string) ($options['out'] ?? getcwd().'/epic-export'), '/');

if (! is_file($app.'/artisan')) {
    fail("{$app} does not look like the EPIC Laravel project (no artisan file). Pass --app=DIR.");
}

$db = database_settings($app);

if (isset($options['print-db-env'])) {
    foreach (['driver' => 'DRIVER', 'host' => 'HOST', 'port' => 'PORT', 'database' => 'NAME', 'username' => 'USER', 'password' => 'PASS', 'socket' => 'SOCKET', 'source' => 'SOURCE', 'url' => 'URL'] as $key => $name) {
        echo 'LARAVEL_DB_'.$name.'='.escapeshellarg((string) $db[$key]).PHP_EOL;
    }
    exit(0);
}

try {
    $pdo = connect($db, $app);
} catch (PDOException $e) {
    fail('Cannot connect to the database ('.$db['source'].'): '.$e->getMessage());
}

if (! is_dir($out) && ! mkdir($out, 0755, true) && ! is_dir($out)) {
    fail("Cannot create {$out}");
}

$data = [
    'format' => FORMAT,
    'exported_at' => gmdate('c'),
    'source_url' => $db['url'],
    'timezone' => $db['timezone'] ?: 'UTC',
    'admins' => [],
    'tables' => [],
];

foreach (TABLES as $table) {
    $data['tables'][$table] = dump_table($pdo, $table);
    say(sprintf('  %-24s %d rows', $table, count($data['tables'][$table])));
}

$data['admins'] = dashboard_admins($pdo);
$data['visits'] = visit_summary($pdo);
say(sprintf('  %-24s %d visitors, %d page views', 'visits', $data['visits']['visitors'], $data['visits']['page_views']));

$data['files'] = isset($options['no-files']) ? [] : copy_uploads($app, $out);
say(sprintf('  %-24s %d files, %.1f MB', 'uploads', count($data['files']), array_sum(array_column($data['files'], 'size')) / 1048576));

$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);

if ($json === false || file_put_contents($out.'/export.json', $json) === false) {
    fail('Cannot write '.$out.'/export.json');
}

say("Exported to {$out}/export.json");
exit(0);
