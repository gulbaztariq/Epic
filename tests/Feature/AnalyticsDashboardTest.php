<?php

namespace Tests\Feature;

use App\Models\IpLocation;
use App\Models\Setting;
use App\Models\User;
use App\Models\Visit;
use App\Support\Chart;
use App\Support\ReportRange;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AnalyticsDashboardTest extends TestCase
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

    /** Traffic spread over time, countries and devices. */
    protected function seedTraffic(): void
    {
        Visit::factory()->count(4)->create([
            'visited_at' => now()->setTime(10, 0),
            'country_code' => 'PK', 'country' => 'Pakistan', 'city' => 'Islamabad',
            'device_type' => 'desktop', 'path' => '/publications',
        ]);

        Visit::factory()->count(2)->create([
            'visited_at' => now()->subDays(3)->setTime(9, 0),
            'country_code' => 'GB', 'country' => 'United Kingdom', 'city' => 'London',
            'device_type' => 'mobile', 'path' => '/events', 'referrer_host' => 'google.com',
        ]);

        Visit::factory()->count(6)->create([
            'visited_at' => now()->subDays(45)->setTime(14, 0),
            'country_code' => 'US', 'country' => 'United States', 'city' => 'New York',
            'path' => '/',
        ]);

        Visit::factory()->bot()->count(5)->create(['visited_at' => now()->setTime(11, 0)]);
    }

    /* ------------------------------------------------------------- Access */

    public function test_analytics_requires_a_signed_in_dashboard_user(): void
    {
        $this->get('/admin/analytics')->assertRedirect('/admin/login');
        $this->get('/admin/analytics/visitors')->assertRedirect('/admin/login');
    }

    public function test_editors_can_read_the_reports(): void
    {
        $editor = User::factory()->create(['role' => 'editor', 'is_active' => true]);

        $this->actingAs($editor)->get('/admin/analytics')->assertOk();
        $this->actingAs($editor)->get('/admin/analytics/visitors')->assertOk();
    }

    /* ----------------------------------------------------------- Overview */

    public function test_the_overview_reports_the_headline_figures(): void
    {
        $this->seedTraffic();

        $this->actingAs($this->admin)
            ->get('/admin/analytics?range=30days')
            ->assertOk()
            ->assertSee('Visitor analytics')
            ->assertSee('Page views')
            ->assertSee('Most read pages')
            ->assertSee('/publications')
            ->assertSee('Pakistan')
            ->assertSee('Islamabad')
            ->assertSee('google.com')
            ->assertSeeText('6');  // 4 + 2 human page views in the last 30 days
    }

    public function test_the_overview_shows_an_empty_state_before_any_visits(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/analytics')
            ->assertOk()
            ->assertSee('No visits have been recorded yet');
    }

    public function test_a_warning_appears_when_tracking_is_switched_off(): void
    {
        Setting::put('analytics_enabled', '0');
        Setting::flush();

        $this->actingAs($this->admin)
            ->get('/admin/analytics')
            ->assertOk()
            ->assertSee('Visitor counting is switched off');
    }

    /* ------------------------------------------------------ Date ranges */

    public function test_presets_scope_every_figure_on_the_page(): void
    {
        $this->seedTraffic();
        $this->actingAs($this->admin);

        // Today: only today's four human views.
        $today = $this->get('/admin/analytics?range=today');
        $today->assertOk()->assertSee('Today');
        $this->assertSame(4, $this->totalsFrom('today')['page_views']['value']);

        // Last 30 days: today's four plus two from three days ago.
        $this->assertSame(6, $this->totalsFrom('30days')['page_views']['value']);

        // Last 90 days also picks up the older six.
        $this->assertSame(12, $this->totalsFrom('90days')['page_views']['value']);
    }

    public function test_a_custom_date_range_is_respected(): void
    {
        $this->seedTraffic();

        $from = now()->subDays(5)->toDateString();
        $to = now()->subDays(1)->toDateString();

        $response = $this->actingAs($this->admin)->get("/admin/analytics?from={$from}&to={$to}");

        $response->assertOk()->assertSee(now()->subDays(5)->format('d M Y'));
        $this->assertSame(2, $response->viewData('totals')['page_views']['value']);
    }

    public function test_a_reversed_custom_range_is_corrected(): void
    {
        $range = ReportRange::fromRequest(request()->merge([
            'from' => now()->toDateString(),
            'to' => now()->subDays(7)->toDateString(),
        ]));

        $this->assertTrue($range->from->lt($range->to));
        $this->assertSame(8, $range->dayCount());
    }

    public function test_bots_are_excluded_unless_they_are_asked_for(): void
    {
        $this->seedTraffic();

        $this->assertSame(4, $this->totalsFrom('today')['page_views']['value']);

        $response = $this->actingAs($this->admin)->get('/admin/analytics?range=today&bots=1');
        $this->assertSame(9, $response->viewData('totals')['page_views']['value']);
    }

    public function test_a_single_day_range_is_charted_by_hour(): void
    {
        $this->seedTraffic();

        $response = $this->actingAs($this->admin)->get('/admin/analytics?range=today');
        $chart = $response->viewData('chart');

        $this->assertCount(24, $chart['columns']);
        $this->assertSame('10:00', $chart['columns'][10]['label']);
        $this->assertSame(4, $chart['columns'][10]['values'][0]['value']);
        $response->assertSee('by hour');
    }

    public function test_the_chart_draws_both_series(): void
    {
        $this->seedTraffic();

        $response = $this->actingAs($this->admin)->get('/admin/analytics?range=30days');
        $chart = $response->viewData('chart');

        $this->assertCount(2, $chart['series']);
        $this->assertSame('Page views', $chart['series'][0]['label']);
        $this->assertSame('Visitors', $chart['series'][1]['label']);
        $this->assertStringStartsWith('M', $chart['series'][0]['line']);
        $this->assertFalse($chart['isEmpty']);

        $response->assertSee('stroke="#0f6fc0"', false)
            ->assertSee('stroke="#41a62a"', false);
    }

    public function test_the_chart_axis_uses_rounded_whole_numbers(): void
    {
        [$top, $step] = Chart::niceScale(37);

        $this->assertSame(40.0, (float) $top);
        $this->assertSame(10.0, (float) $step);

        [$emptyTop, $emptyStep] = Chart::niceScale(0);
        $this->assertSame(4.0, (float) $emptyTop);
        $this->assertSame(1.0, (float) $emptyStep);
    }

    /* ---------------------------------------------------------- Visitor log */

    public function test_the_visitor_log_lists_visits_newest_first(): void
    {
        $this->seedTraffic();

        $response = $this->actingAs($this->admin)->get('/admin/analytics/visitors?range=90days');

        $response->assertOk()->assertSee('/publications')->assertSee('Islamabad');
        $this->assertSame(12, $response->viewData('visits')->total());
    }

    public function test_the_visitor_log_can_be_filtered(): void
    {
        $this->seedTraffic();
        $this->actingAs($this->admin);

        $byCountry = $this->get('/admin/analytics/visitors?range=90days&country=GB');
        $this->assertSame(2, $byCountry->viewData('visits')->total());

        $byDevice = $this->get('/admin/analytics/visitors?range=90days&device=mobile');
        $this->assertSame(2, $byDevice->viewData('visits')->total());

        $byPath = $this->get('/admin/analytics/visitors?range=90days&path=publications');
        $this->assertSame(4, $byPath->viewData('visits')->total());
    }

    /* --------------------------------------------------------------- Export */

    public function test_the_summary_csv_can_be_downloaded(): void
    {
        $this->seedTraffic();

        $response = $this->actingAs($this->admin)
            ->get('/admin/analytics/export?range=30days&type=daily')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('EPIC website report', $csv);
        $this->assertStringContainsString('Page views', $csv);
        $this->assertStringContainsString('Top countries', $csv);
        $this->assertStringContainsString('Pakistan', $csv);
        $this->assertStringContainsString('Top cities', $csv);
        $this->assertStringContainsString('Islamabad', $csv);
        $this->assertStringContainsString('google.com', $csv);
    }

    public function test_the_full_visitor_log_csv_can_be_downloaded(): void
    {
        $this->seedTraffic();

        $csv = $this->actingAs($this->admin)
            ->get('/admin/analytics/export?range=90days&type=visits')
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Date and time', $csv);
        $this->assertStringContainsString('/publications', $csv);
        $this->assertStringContainsString('United Kingdom', $csv);
        $this->assertSame(12 + 6, substr_count(trim($csv), "\n") + 1, 'Header block plus one row per visit.');
    }

    public function test_the_export_filename_carries_the_period(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/analytics/export?range=today&type=daily')
            ->assertOk()
            ->assertDownload('epic-visitor-summary-'.now()->toDateString().'-to-'.now()->toDateString().'.csv');
    }

    /* ----------------------------------------------------- Manual lookups */

    public function test_locations_can_be_resolved_from_the_dashboard(): void
    {
        Http::fake(['ipwho.is/*' => Http::response([
            'success' => true,
            'country' => 'Pakistan',
            'country_code' => 'PK',
            'city' => 'Lahore',
            'timezone' => ['id' => 'Asia/Karachi'],
        ])]);

        $location = IpLocation::create(['ip_hash' => hash('sha256', 'x'), 'ip_address' => '203.0.113.5']);
        Visit::factory()->create(['ip_hash' => $location->ip_hash, 'location_resolved' => false, 'country' => null, 'city' => null]);

        $this->actingAs($this->admin)
            ->post('/admin/analytics/resolve-locations')
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('Lahore', Visit::sole()->city);
    }

    public function test_the_dashboard_shows_visitor_figures(): void
    {
        $this->seedTraffic();

        $this->actingAs($this->admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Visitors today')
            ->assertSee('Open analytics');
    }

    /** Helper: the totals the overview renders for a preset. */
    protected function totalsFrom(string $preset): array
    {
        return $this->actingAs($this->admin)
            ->get('/admin/analytics?range='.$preset)
            ->viewData('totals');
    }
}
