<?php

namespace Tests\Feature;

use App\Console\Commands\InstallCommand;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * A first install on hosting without SSH leaves APP_KEY blank and lets the installer
 * generate it. The key was written to .env, but the configuration cache built in the
 * same process froze in the empty value, so the installer reported success and then
 * every page failed with "No application encryption key has been specified".
 */
class InstallCommandTest extends TestCase
{
    /** @var list<string> */
    private array $cleanup = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanup as $path) {
            File::isDirectory($path) ? File::deleteDirectory($path) : @unlink($path);
        }

        foreach (['APP_CONFIG_CACHE'] as $name) {
            unset($_ENV[$name], $_SERVER[$name]);
            putenv($name);
        }

        parent::tearDown();
    }

    public function test_a_generated_key_reaches_the_running_process(): void
    {
        $original = ['env' => $_ENV['APP_KEY'] ?? null, 'server' => $_SERVER['APP_KEY'] ?? null, 'putenv' => getenv('APP_KEY')];

        try {
            $_ENV['APP_KEY'] = $_SERVER['APP_KEY'] = '';
            putenv('APP_KEY=');

            InstallCommand::exportKey('base64:GeneratedByTheInstaller=');

            $this->assertSame('base64:GeneratedByTheInstaller=', env('APP_KEY'));
            $this->assertSame('base64:GeneratedByTheInstaller=', config('app.key'));
            $this->assertSame('base64:GeneratedByTheInstaller=', getenv('APP_KEY'));
        } finally {
            $original['env'] === null ? $_ENV = array_diff_key($_ENV, ['APP_KEY' => 1]) : $_ENV['APP_KEY'] = $original['env'];
            $original['server'] === null ? $_SERVER = array_diff_key($_SERVER, ['APP_KEY' => 1]) : $_SERVER['APP_KEY'] = $original['server'];
            $original['putenv'] === false ? putenv('APP_KEY') : putenv('APP_KEY='.$original['putenv']);
            config(['app.key' => $original['env']]);
        }
    }

    /**
     * The real thing: run the installer in its own PHP process with APP_KEY defined but
     * empty, exactly as the browser installer starts, and check that the cache it leaves
     * behind holds the key it generated. Anything less does not reproduce the failure,
     * because it only exists across the process boundary.
     */
    public function test_a_first_install_with_a_blank_key_leaves_a_cached_config_that_has_the_key(): void
    {
        $tmp = sys_get_temp_dir().'/epic-install-'.bin2hex(random_bytes(4));
        $envFile = base_path('.env.epicinstalltest');
        File::ensureDirectoryExists($tmp.'/views');
        touch($tmp.'/db.sqlite');
        file_put_contents($envFile, "APP_KEY=\nAPP_ENV=epicinstalltest\n");
        array_push($this->cleanup, $tmp, $envFile);

        $process = new Process([PHP_BINARY, 'artisan', 'epic:install', '--optimize', '--no-interaction', '--no-seed'], base_path(), [
            'APP_ENV' => 'epicinstalltest',      // loads .env.epicinstalltest, which key:generate will write to
            'APP_KEY' => '',                     // defined but empty: what the loader refuses to overwrite
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => $tmp.'/db.sqlite',
            'APP_CONFIG_CACHE' => $tmp.'/config.php',
            'APP_ROUTES_CACHE' => $tmp.'/routes.php',
            'APP_EVENTS_CACHE' => $tmp.'/events.php',
            'VIEW_COMPILED_PATH' => $tmp.'/views',   // view:cache empties this folder, so keep it apart from the rest
            'SESSION_DRIVER' => 'array',
            'CACHE_STORE' => 'array',
        ]);
        $process->setTimeout(180)->run();

        $this->assertSame(0, $process->getExitCode(), $process->getOutput().$process->getErrorOutput());
        $this->assertFileExists($tmp.'/config.php');

        preg_match('/^APP_KEY=(.+)$/m', file_get_contents($envFile), $written);
        $this->assertNotEmpty($written[1] ?? null, 'The installer did not write a key to .env.');

        $cached = require $tmp.'/config.php';
        $this->assertSame($written[1], $cached['app']['key'], 'The cached configuration does not hold the key the installer generated.');
    }

    public function test_a_cache_with_no_key_is_removed_rather_than_left_to_break_the_site(): void
    {
        $tmp = sys_get_temp_dir().'/epic-cache-'.bin2hex(random_bytes(4));
        File::ensureDirectoryExists($tmp);
        $this->cleanup[] = $tmp;

        // Point the app at a throwaway config cache that holds an empty key.
        $_ENV['APP_CONFIG_CACHE'] = $_SERVER['APP_CONFIG_CACHE'] = $tmp.'/config.php';
        putenv('APP_CONFIG_CACHE='.$tmp.'/config.php');
        file_put_contents($tmp.'/config.php', "<?php return ['app' => ['key' => '']];");

        $command = new class extends InstallCommand
        {
            public function check(): void
            {
                $this->components = new Factory(new OutputStyle(new ArrayInput([]), new NullOutput));
                $this->refuseCacheWithoutKey();
            }
        };
        $command->setLaravel($this->app);
        $command->check();

        $this->assertFileDoesNotExist($tmp.'/config.php');
    }

    public function test_a_cache_that_has_a_key_is_left_alone(): void
    {
        $tmp = sys_get_temp_dir().'/epic-cache-'.bin2hex(random_bytes(4));
        File::ensureDirectoryExists($tmp);
        $this->cleanup[] = $tmp;

        $_ENV['APP_CONFIG_CACHE'] = $_SERVER['APP_CONFIG_CACHE'] = $tmp.'/config.php';
        putenv('APP_CONFIG_CACHE='.$tmp.'/config.php');
        file_put_contents($tmp.'/config.php', "<?php return ['app' => ['key' => 'base64:present']];");

        $command = new class extends InstallCommand
        {
            public function check(): void
            {
                $this->components = new Factory(new OutputStyle(new ArrayInput([]), new NullOutput));
                $this->refuseCacheWithoutKey();
            }
        };
        $command->setLaravel($this->app);
        $command->check();

        $this->assertFileExists($tmp.'/config.php');
    }
}
