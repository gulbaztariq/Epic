<?php

namespace Tests\Feature;

use App\Models\IpLocation;
use App\Models\Setting;
use App\Models\Visit;
use App\Services\VisitorTracker;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VisitorTrackingTest extends TestCase
{
    use RefreshDatabase;

    private const DESKTOP = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0 Safari/537.36';

    private const IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

    private const GOOGLEBOT = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Http::preventStrayRequests();
    }

    /** A page view with a clean set of request headers each time. */
    protected function browse(string $uri, string $agent = self::DESKTOP, array $headers = [])
    {
        $this->flushHeaders();

        return $this->withHeaders(array_merge(['User-Agent' => $agent], $headers))->get($uri);
    }

    /* --------------------------------------------------------- Recording */

    public function test_a_public_page_view_is_recorded(): void
    {
        $this->browse('/')->assertOk();

        $visit = Visit::sole();

        $this->assertSame('/', $visit->path);
        $this->assertSame('desktop', $visit->device_type);
        $this->assertSame('Chrome', $visit->browser);
        $this->assertSame('Windows', $visit->platform);
        $this->assertFalse($visit->is_bot);
        $this->assertTrue($visit->is_new_visitor);
        $this->assertStringContainsString('EPIC', (string) $visit->page_title);
        $this->assertNotNull($visit->visited_at);
    }

    public function test_every_public_page_is_counted(): void
    {
        foreach (['/', '/publications', '/events', '/contact', '/who-we-are'] as $uri) {
            $this->browse($uri)->assertOk();
        }

        $this->assertSame(5, Visit::count());
        $this->assertEqualsCanonicalizing(
            ['/', '/publications', '/events', '/contact', '/who-we-are'],
            Visit::pluck('path')->all()
        );
    }

    public function test_dashboard_and_utility_paths_are_not_counted(): void
    {
        $this->browse('/admin/login')->assertOk();
        $this->browse('/sitemap.xml')->assertOk();
        $this->browse('/up')->assertOk();

        $this->assertSame(0, Visit::count());
    }

    public function test_form_submissions_are_not_counted_as_page_views(): void
    {
        $this->withHeaders(['User-Agent' => self::DESKTOP])
            ->post('/subscribe', ['email' => 'reader@example.com'])
            ->assertRedirect();

        $this->assertSame(0, Visit::count());
    }

    public function test_a_missing_page_is_still_counted(): void
    {
        $this->browse('/no-such-page')->assertNotFound();

        $this->assertSame('/no-such-page', Visit::sole()->path);
    }

    /* ------------------------------------------------------------- Bots */

    public function test_crawlers_are_recorded_but_flagged(): void
    {
        $this->browse('/', self::GOOGLEBOT)->assertOk();

        $visit = Visit::sole();

        $this->assertTrue($visit->is_bot);
        $this->assertSame('bot', $visit->device_type);
    }

    public function test_crawlers_can_be_left_out_altogether(): void
    {
        Setting::put('analytics_track_bots', '0');
        Setting::flush();

        $this->browse('/', self::GOOGLEBOT)->assertOk();

        $this->assertSame(0, Visit::count());
    }

    /* ----------------------------------------------------- Switches */

    public function test_tracking_can_be_switched_off(): void
    {
        Setting::put('analytics_enabled', '0');
        Setting::flush();

        $this->browse('/')->assertOk();

        $this->assertSame(0, Visit::count());
    }

    public function test_do_not_track_is_honoured_when_enabled(): void
    {
        Setting::put('analytics_respect_dnt', '1');
        Setting::flush();

        $this->browse('/', self::DESKTOP, ['DNT' => '1'])->assertOk();
        $this->assertSame(0, Visit::count());

        $this->browse('/')->assertOk();
        $this->assertSame(1, Visit::count());
    }

    /* ------------------------------------------------------------ Privacy */

    public function test_ip_addresses_are_anonymised_by_default(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])->browse('/')->assertOk();

        $this->assertSame('203.0.113.0', Visit::sole()->ip_address);
    }

    public function test_the_real_visitor_ip_is_recorded_behind_a_proxy(): void
    {
        // What Railway (or any edge proxy) sends: the load balancer as the
        // socket address, the actual visitor in X-Forwarded-For.
        $this->withServerVariables(['REMOTE_ADDR' => '10.11.12.13'])
            ->browse('/', self::DESKTOP, ['X-Forwarded-For' => '203.0.113.42']);

        $this->assertSame('203.0.113.0', Visit::sole()->ip_address,
            'The forwarded visitor address must be used, not the proxy address.');
    }

    public function test_https_urls_are_generated_behind_a_proxy(): void
    {
        $response = $this->withHeaders([
            'User-Agent' => self::DESKTOP,
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'epic.example.com',
        ])->get('/');

        $response->assertOk()->assertSee('https://epic.example.com', false);
    }

    public function test_full_ip_addresses_are_stored_only_when_asked_for(): void
    {
        Setting::put('analytics_store_full_ip', '1');
        Setting::flush();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])->browse('/')->assertOk();

        $this->assertSame('203.0.113.42', Visit::sole()->ip_address);
    }

    /* -------------------------------------------------- Devices & sources */

    public function test_mobile_devices_are_detected(): void
    {
        $this->browse('/', self::IPHONE)->assertOk();

        $visit = Visit::sole();

        $this->assertSame('mobile', $visit->device_type);
        $this->assertSame('Safari', $visit->browser);
        $this->assertSame('iOS', $visit->platform);
    }

    public function test_external_referrers_are_recorded_and_internal_ones_ignored(): void
    {
        $this->browse('/publications', self::DESKTOP, ['referer' => 'https://www.google.com/search?q=epic'])->assertOk();
        $this->assertSame('google.com', Visit::latest('id')->first()->referrer_host);

        $this->browse('/events', self::DESKTOP, ['referer' => url('/publications')])->assertOk();
        $this->assertNull(Visit::latest('id')->first()->referrer_host);
    }

    public function test_a_returning_visitor_is_only_new_once(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])->browse('/')->assertOk();
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])->browse('/events')->assertOk();

        $visits = Visit::orderBy('id')->get();

        $this->assertTrue($visits[0]->is_new_visitor);
        $this->assertFalse($visits[1]->is_new_visitor);
        $this->assertSame($visits[0]->visitor_key, $visits[1]->visitor_key);
    }

    /* ------------------------------------------------------ Footer counter */

    public function test_the_footer_counter_shows_the_visitor_total(): void
    {
        Visit::factory()->count(3)->create();
        Visit::forgetCounters();

        $this->browse('/')->assertOk()->assertSee('Website visitors');
    }

    public function test_the_counter_can_be_hidden(): void
    {
        Setting::put('show_visitor_counter', '0');
        Setting::flush();

        $this->browse('/')->assertOk()->assertDontSee('Website visitors');
    }

    public function test_the_counter_can_show_page_views_instead(): void
    {
        Setting::put('visitor_counter_metric', 'views');
        Setting::put('visitor_counter_label', 'Pages read');
        Setting::flush();

        Visit::factory()->count(4)->create();
        Visit::forgetCounters();

        $this->browse('/')->assertOk()->assertSee('Pages read');
    }

    public function test_bots_are_left_out_of_the_public_counter(): void
    {
        Visit::factory()->count(2)->create();
        Visit::factory()->bot()->count(5)->create();
        Visit::forgetCounters();

        $this->assertSame(2, Visit::totalVisitors());
        $this->assertSame(2, Visit::totalPageViews());
    }

    /* --------------------------------------------------------- Geolocation */

    public function test_a_previously_resolved_address_is_applied_immediately(): void
    {
        $tracker = app(VisitorTracker::class);
        $ipHash = (fn () => $this->hash('198.51.100.20'))->call($tracker);

        IpLocation::create([
            'ip_hash' => $ipHash,
            'country_code' => 'PK',
            'country' => 'Pakistan',
            'region' => 'Sindh',
            'city' => 'Karachi',
            'resolved_at' => now(),
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])->browse('/')->assertOk();

        $visit = Visit::sole();

        $this->assertSame('Pakistan', $visit->country);
        $this->assertSame('Karachi', $visit->city);
        $this->assertTrue($visit->location_resolved);
    }

    public function test_a_cdn_country_header_is_used_when_present(): void
    {
        $this->browse('/', self::DESKTOP, ['CF-IPCountry' => 'gb'])->assertOk();

        $visit = Visit::sole();

        $this->assertSame('GB', $visit->country_code);
        $this->assertSame('United Kingdom', $visit->country);
    }

    public function test_new_addresses_are_queued_for_a_lookup(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.33'])->browse('/')->assertOk();

        $this->assertSame(1, IpLocation::count());
        $this->assertSame('198.51.100.33', IpLocation::sole()->ip_address);
        $this->assertNull(IpLocation::sole()->resolved_at);
    }

    public function test_private_addresses_are_never_sent_to_the_lookup_service(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.10'])->browse('/')->assertOk();

        $this->assertNull(IpLocation::sole()->ip_address);
        $this->assertSame(0, IpLocation::pending()->count());
    }

    public function test_pending_addresses_are_resolved_and_copied_onto_visits(): void
    {
        Http::fake(['ipwho.is/*' => Http::response([
            'success' => true,
            'country' => 'Pakistan',
            'country_code' => 'PK',
            'region' => 'Islamabad Capital Territory',
            'city' => 'Islamabad',
            'latitude' => 33.6844,
            'longitude' => 73.0479,
            'timezone' => ['id' => 'Asia/Karachi'],
            'connection' => ['org' => 'Example ISP'],
        ])]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.99'])->browse('/')->assertOk();

        $this->artisan('epic:resolve-visitor-locations')->assertSuccessful();

        $location = IpLocation::sole();
        $this->assertSame('PK', $location->country_code);
        $this->assertSame('Islamabad', $location->city);
        $this->assertNotNull($location->resolved_at);
        $this->assertNull($location->ip_address, 'The address should be forgotten once resolved.');

        $visit = Visit::sole();
        $this->assertSame('Pakistan', $visit->country);
        $this->assertSame('Islamabad', $visit->city);
        $this->assertTrue($visit->location_resolved);
    }

    public function test_failed_lookups_are_retried_a_limited_number_of_times(): void
    {
        Http::fake(['ipwho.is/*' => Http::response(['success' => false, 'message' => 'Reserved range'])]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.77'])->browse('/')->assertOk();

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $this->artisan('epic:resolve-visitor-locations')->assertSuccessful();
        }

        $location = IpLocation::sole();

        $this->assertNull($location->resolved_at);
        $this->assertSame(IpLocation::MAX_ATTEMPTS, $location->attempts);
        $this->assertSame(0, IpLocation::pending()->count());
    }

    public function test_lookups_are_skipped_when_geolocation_is_switched_off(): void
    {
        Setting::put('analytics_geolocation', '0');
        Setting::flush();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.55'])->browse('/')->assertOk();

        $this->assertSame(0, IpLocation::count());
        $this->artisan('epic:resolve-visitor-locations')->assertSuccessful();
    }

    /* ------------------------------------------------------------ Pruning */

    public function test_old_visits_are_pruned_and_recent_ones_kept(): void
    {
        Visit::factory()->on(now()->subDays(400))->count(3)->create();
        Visit::factory()->on(now()->subDays(10))->count(2)->create();

        $this->artisan('epic:prune-visits', ['--days' => 365])->assertSuccessful();

        $this->assertSame(2, Visit::count());
    }

    public function test_pruning_keeps_everything_when_retention_is_zero(): void
    {
        Visit::factory()->on(now()->subYears(5))->count(2)->create();

        $this->artisan('epic:prune-visits', ['--days' => 0])->assertSuccessful();

        $this->assertSame(2, Visit::count());
    }
}
