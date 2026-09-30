<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\Theme;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppearanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('role', 'super_admin')->firstOrFail();
    }

    public function test_the_menu_bar_has_a_light_background_by_default(): void
    {
        $this->get('/')->assertOk()->assertSee('--menu-bg:#e8f1fa;', false);
        $this->assertStringContainsString('var(--menu-bg', file_get_contents(public_path('css/site.css')));
    }

    public function test_the_page_header_picture_is_only_lightly_darkened_by_default(): void
    {
        $this->get('/')->assertOk()->assertSee('--hero-overlay:0.5;', false);

        // The old wash was ~90% opaque, which buried the picture.
        $this->assertStringNotContainsString('rgba(13,36,80,.94)', file_get_contents(public_path('css/site.css')));
    }

    public function test_the_appearance_screen_loads_with_the_defaults(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/settings/appearance')
            ->assertOk()
            ->assertSee('Menu bar background')
            ->assertSee('#e8f1fa')
            ->assertSee('Page header picture');
    }

    public function test_an_admin_can_change_the_menu_colour_and_header_darkening(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/settings/appearance', ['menu_background' => '#ddeeff', 'page_header_overlay' => '25'])
            ->assertRedirect('/admin/settings/appearance');

        Setting::flush();

        $this->get('/')->assertSee('--menu-bg:#ddeeff;', false)->assertSee('--hero-overlay:0.25;', false);
    }

    public function test_zero_darkening_is_allowed(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/settings/appearance', ['menu_background' => '#ffffff', 'page_header_overlay' => '0'])
            ->assertRedirect();

        Setting::flush();

        $this->get('/')->assertSee('--hero-overlay:0;', false);
    }

    public function test_invalid_values_are_rejected(): void
    {
        $this->actingAs($this->admin)
            ->from('/admin/settings/appearance')
            ->put('/admin/settings/appearance', ['menu_background' => 'red; } body { display:none', 'page_header_overlay' => '500'])
            ->assertSessionHasErrors(['menu_background', 'page_header_overlay']);
    }

    public function test_a_tampered_stored_value_can_never_reach_the_stylesheet(): void
    {
        Setting::put('menu_background', 'red; } body { display:none');
        Setting::put('page_header_overlay', 'abc');
        Setting::flush();

        $this->assertSame(Theme::DEFAULT_MENU_BACKGROUND, Theme::menuBackground());
        $this->assertSame(Theme::DEFAULT_HEADER_OVERLAY, Theme::headerOverlay());
        // The tampered text is not in the page; the defaults are.
        $this->get('/')
            ->assertOk()
            ->assertDontSee('body { display:none', false)
            ->assertSee('--menu-bg:#e8f1fa;--hero-overlay:0.5;', false);
    }

    public function test_the_overlay_is_capped_so_a_picture_is_never_fully_hidden(): void
    {
        Setting::put('page_header_overlay', '100');
        Setting::flush();

        $this->assertSame(Theme::MAX_HEADER_OVERLAY, Theme::headerOverlay());
    }
}
