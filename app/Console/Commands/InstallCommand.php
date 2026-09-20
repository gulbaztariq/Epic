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
            $this->components->task('Generating application key', fn () => Artisan::call('key:generate', ['--force' => true]) === 0);
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
}
