<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class InstallCommand extends Command
{
    protected $signature = 'epic:install
                            {--fresh : Drop all tables and rebuild the database}
                            {--no-seed : Skip the starter content}
                            {--optimize : Cache config, routes and views for production}';

    protected $description = 'Prepare the EPIC website: database, starter content and upload folders';

    public function handle(): int
    {
        $this->components->info('Installing the EPIC website');

        // 1. Application key
        if (blank(config('app.key'))) {
            $this->components->task('Generating application key', function () {
                return Artisan::call('key:generate', ['--force' => true]) === 0 && $this->adoptGeneratedKey();
            });
        } else {
            $this->components->twoColumnDetail('Application key', '<fg=green>already set</>');
        }

        // 2. Upload folders
        $this->components->task('Preparing upload folders', function () {
            foreach (['general', 'pages', 'publications', 'events', 'posts', 'projects', 'people', 'partners', 'chapters', 'gallery', 'podcast', 'videos', 'branding', 'avatars', 'lists', 'volunteers', 'sections'] as $folder) {
                File::ensureDirectoryExists(public_path('uploads/'.$folder), 0755);
            }

            return true;
        });

        // 3. Database
        if ($this->option('fresh')) {
            if (! $this->option('no-interaction') && ! $this->confirm('This will DELETE all existing website data. Continue?', false)) {
                $this->components->warn('Installation cancelled.');

                return self::FAILURE;
            }

            $this->components->task('Rebuilding the database', fn () => Artisan::call('migrate:fresh', ['--force' => true]) === 0);
        } else {
            $this->components->task('Running database migrations', fn () => Artisan::call('migrate', ['--force' => true]) === 0);
        }

        // 4. Content
        if (! $this->option('no-seed')) {
            $hasContent = Schema::hasTable('pages') && Page::exists();

            if ($hasContent && ! $this->option('fresh')) {
                $this->components->twoColumnDetail('Starter content', '<fg=yellow>skipped — content already exists</>');
            } else {
                $this->components->task('Adding pages, menus and starter content', fn () => Artisan::call('db:seed', ['--force' => true]) === 0);
            }
        }

        // 5. Production caches
        if ($this->option('optimize')) {
            $this->components->task('Caching configuration, routes and views', fn () => Artisan::call('optimize') === 0);
            $this->refuseCacheWithoutKey();
        } else {
            Artisan::call('optimize:clear');
        }

        $admin = User::where('role', 'super_admin')->first();

        $this->newLine();
        $this->components->info('EPIC is ready.');
        $this->components->twoColumnDetail('Website', config('app.url'));
        $this->components->twoColumnDetail('Dashboard', rtrim((string) config('app.url'), '/').'/admin');

        if ($admin) {
            $this->components->twoColumnDetail('Super admin', $admin->email);
            $this->components->warn('Sign in and change the super admin password straight away (Dashboard > My profile).');
        }

        return self::SUCCESS;
    }

    /**
     * Hand a freshly generated key to this running process.
     *
     * key:generate writes the key to .env, but this process loaded .env before the key
     * existed, and Laravel's environment loader never overwrites a variable that is already
     * defined, even as an empty string. Everything built later in the same process, above
     * all the configuration cache, would still see an empty APP_KEY and freeze it in: the
     * installer reported success and then every page failed with "No application
     * encryption key has been specified".
     */
    public static function exportKey(string $key): void
    {
        putenv('APP_KEY='.$key);
        $_ENV['APP_KEY'] = $key;
        $_SERVER['APP_KEY'] = $key;

        config(['app.key' => $key]);
    }

    protected function adoptGeneratedKey(): bool
    {
        $file = $this->laravel->environmentFilePath();

        if (! is_file($file) || ! preg_match('/^APP_KEY=(.+)$/m', (string) file_get_contents($file), $found)) {
            return false;
        }

        static::exportKey(trim($found[1], " \t\"'"));

        return true;
    }

    /**
     * Never leave behind a cached configuration that has no key: it would make every page
     * fail, and on hosting without SSH there is no way to notice or repair it from a
     * browser. Without the cache the site reads .env directly, which works.
     */
    protected function refuseCacheWithoutKey(): void
    {
        $cache = $this->laravel->getCachedConfigPath();

        if (! is_file($cache)) {
            return;
        }

        $cached = require $cache;

        if (filled($cached['app']['key'] ?? null)) {
            return;
        }

        Artisan::call('optimize:clear');
        $this->components->warn('The application key was missing from the cached configuration, so the caches were removed. '
            .'The site will read .env directly; check that APP_KEY is set there.');
    }
}
