<?php

namespace Tests\Feature;

use App\Models\ListItem;
use App\Models\PageSection;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_the_three_item_strip_under_the_hero_is_gone(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Evidence for Policy')
            ->assertDontSee('Ideas for People')
            ->assertDontSee('Inclusive Growth for Pakistan')
            ->assertDontSee('hero-highlights', false);

        $this->assertFalse(ListItem::where('group', 'hero_highlights')->exists());
        $this->assertArrayNotHasKey('hero_highlights', ListItem::GROUPS);
    }

    public function test_the_focus_areas_heading_has_no_link_or_accent_dash(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('A more innovative, competitive and inclusive Pakistan', $html);

        // Every front-page heading drops the green dash, not just this one.
        $this->assertMatchesRegularExpression('/<h2 class="section-title is-plain">Our Focus Areas<\/h2>/', $html);
        $this->assertStringNotContainsString('<h2 class="section-title">', $html);
        $this->assertStringNotContainsString('background:var(--green);border-radius:2px', $html);
    }

    public function test_the_focus_link_returns_if_an_editor_writes_one(): void
    {
        PageSection::where('type', 'focus')->update(['link_text' => 'See every theme', 'link_url' => '/what-we-do/themes']);

        $this->get('/')->assertOk()->assertSee('See every theme');
    }

    public function test_the_focus_areas_are_still_listed(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Economic Policy')
            ->assertSee('Digital Economy')
            ->assertSee('class="focus-item', false);
    }

    public function test_the_clean_up_migration_removes_the_old_strip_and_link_from_a_live_database(): void
    {
        foreach ([['Evidence for Policy', 'document'], ['Ideas for People', 'lightbulb']] as $i => [$title, $icon]) {
            ListItem::create(['group' => 'hero_highlights', 'title' => $title, 'icon' => $icon, 'sort' => $i, 'is_active' => true]);
        }
        PageSection::where('type', 'focus')->update([
            'link_text' => 'A more innovative, competitive and inclusive Pakistan',
            'link_url' => '/what-we-do/themes',
        ]);

        (include database_path('migrations/2026_09_29_000003_remove_home_highlights_and_focus_link.php'))->up();

        $this->assertFalse(ListItem::where('group', 'hero_highlights')->exists());
        $focus = PageSection::where('type', 'focus')->firstOrFail();
        $this->assertNull($focus->link_text);
        $this->assertNull($focus->link_url);
    }

    public function test_the_clean_up_migration_keeps_a_link_an_editor_wrote(): void
    {
        PageSection::where('type', 'focus')->update(['link_text' => 'See every theme', 'link_url' => '/what-we-do/themes']);

        (include database_path('migrations/2026_09_29_000003_remove_home_highlights_and_focus_link.php'))->up();

        $this->assertSame('See every theme', PageSection::where('type', 'focus')->value('link_text'));
    }
}
