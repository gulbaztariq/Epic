<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\WebScheduler;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('role', 'super_admin')->firstOrFail();
        Http::preventStrayRequests();
    }

    protected function scheduler(): WebScheduler
    {
        return app(WebScheduler::class);
    }

    /* ------------------------------------------------- Scheduling without cron */

    public function test_due_tasks_run_and_record_when_they_last_ran(): void
    {
        $ran = $this->scheduler()->runDue();

        $this->assertCount(count(WebScheduler::TASKS), $ran);
        $this->assertNotNull($this->scheduler()->lastRunAt('visitor-locations'));
        $this->assertNotNull($this->scheduler()->lastRunAt('prune-visits'));
    }

    public function test_a_task_does_not_run_again_inside_its_interval(): void
    {
        $this->scheduler()->runDue();

        $this->assertSame([], $this->scheduler()->runDue());
    }

    public function test_a_task_runs_again_once_its_interval_has_passed(): void
    {
        $this->scheduler()->runDue();

        Cache::forever('epic.scheduler.last.visitor-locations', now()->subMinutes(20)->timestamp);

        $ran = collect($this->scheduler()->runDue(force: false))->pluck('key')->all();

        $this->assertSame(['visitor-locations'], $ran);
    }

    public function test_tasks_can_be_forced_to_run(): void
    {
        $this->scheduler()->runDue();

        $this->assertCount(count(WebScheduler::TASKS), $this->scheduler()->runDue(force: true));
    }

    public function test_checking_for_work_is_throttled(): void
    {
        $this->assertTrue($this->scheduler()->shouldCheck());

        $this->scheduler()->runDue();

        $this->assertFalse($this->scheduler()->shouldCheck());
    }

    public function test_concurrent_requests_do_not_run_the_same_task_twice(): void
    {
        Cache::add('epic.scheduler.lock', 1, 120);

        $this->assertSame([], $this->scheduler()->runDue(force: true));
    }

    public function test_the_scheduler_can_be_switched_off(): void
    {
        Setting::put('web_scheduler_enabled', '0');
        Setting::flush();

        $this->assertFalse($this->scheduler()->isEnabled());
    }

    /* --------------------------------------------------- Running from a page */

    public function test_a_page_visit_runs_due_work(): void
    {
        $this->get('/')->assertOk();

        $this->assertNotNull($this->scheduler()->lastRunAt('visitor-locations'));
    }

    public function test_a_page_visit_does_not_run_work_when_switched_off(): void
    {
        Setting::put('web_scheduler_enabled', '0');
        Setting::flush();

        $this->get('/')->assertOk();

        $this->assertNull($this->scheduler()->lastRunAt('visitor-locations'));
    }

    public function test_a_broken_scheduler_never_breaks_a_page(): void
    {
        $this->app->instance(WebScheduler::class, new class extends WebScheduler
        {
            public function isEnabled(): bool
            {
                throw new \RuntimeException('housekeeping exploded');
            }
        });

        $this->get('/')->assertOk()->assertSee('Our Focus Areas');
    }

    /* ------------------------------------------------ Dashboard housekeeping */

    public function test_housekeeping_actions_need_a_signed_in_manager(): void
    {
        $this->post('/admin/maintenance/run-tasks')->assertRedirect('/admin/login');
        $this->post('/admin/maintenance/refresh-caches')->assertRedirect('/admin/login');

        $editor = User::factory()->create(['role' => 'editor', 'is_active' => true]);

        $this->actingAs($editor)->post('/admin/maintenance/run-tasks')->assertForbidden();
        $this->actingAs($editor)->post('/admin/maintenance/refresh-caches')->assertForbidden();
    }

    public function test_an_administrator_can_run_the_tasks_from_the_dashboard(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/maintenance/run-tasks')
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull($this->scheduler()->lastRunAt('visitor-locations'));
    }

    public function test_an_administrator_can_refresh_the_caches(): void
    {
        // Mocked so the test never writes real cache files.
        Artisan::shouldReceive('call')->with('optimize:clear')->once()->andReturn(0);
        Artisan::shouldReceive('call')->with('optimize')->once()->andReturn(0);

        $this->actingAs($this->admin)
            ->post('/admin/maintenance/refresh-caches')
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_a_failed_cache_refresh_is_reported_clearly(): void
    {
        Artisan::shouldReceive('call')->andThrow(new \RuntimeException('permission denied'));

        $this->actingAs($this->admin)
            ->post('/admin/maintenance/refresh-caches')
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_the_dashboard_shows_the_housekeeping_panel_to_managers(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Housekeeping')
            ->assertSee('Look up visitor locations')
            ->assertSee('Run tasks now');
    }

    public function test_editors_do_not_see_the_housekeeping_panel(): void
    {
        $editor = User::factory()->create(['role' => 'editor', 'is_active' => true]);

        $this->actingAs($editor)
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('Housekeeping');
    }

    public function test_the_installer_secret_is_unset_by_default(): void
    {
        $this->assertNull(config('epic.install_token'), 'public/install.php must stay locked unless a secret is set.');
    }
}
