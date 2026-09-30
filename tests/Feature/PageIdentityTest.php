<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Page;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Pages the website depends on are found by a stable key, not by their slug, so
 * renaming one in the dashboard cannot make the site lose it. The Board of
 * Governance → Board of Directors rename is the case that exposed this.
 */
class PageIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('role', 'super_admin')->firstOrFail();
    }

    /* --------------------------------------------------------- The rename --- */

    public function test_the_board_is_called_board_of_directors_everywhere(): void
    {
        $this->get('/who-we-are/board-of-directors')
            ->assertOk()
            ->assertSee('Board of Directors')
            ->assertDontSee('Board of Governance');

        // ...including the menu on every page.
        $this->get('/')
            ->assertOk()
            ->assertSee('Board of Directors')
            ->assertDontSee('Board of Governance');
    }

    public function test_the_old_board_address_redirects_permanently(): void
    {
        $this->get('/who-we-are/board-of-governance')
            ->assertStatus(301)
            ->assertRedirect('/who-we-are/board-of-directors');
    }

    public function test_the_rename_migration_updates_an_existing_database(): void
    {
        // Put the database back into how it looked before the rename.
        Page::where('key', 'board')->update([
            'slug' => 'board-of-governance',
            'title' => 'Board of Governance',
            'intro' => 'The Board of Governance provides oversight.',
        ]);
        MenuItem::where('url', '/who-we-are/board-of-directors')
            ->update(['label' => 'Board of Governance', 'url' => '/who-we-are/board-of-governance']);
        // Wording that merely resembles the old name must survive.
        Page::where('key', 'advisory-council')->update(['intro' => 'Governance and guidance.']);

        (include database_path('migrations/2026_09_29_000002_rename_board_of_governance_to_directors.php'))->up();

        $board = Page::where('key', 'board')->firstOrFail();
        $this->assertSame('board-of-directors', $board->slug);
        $this->assertSame('Board of Directors', $board->title);
        $this->assertSame('The Board of Directors provides oversight.', $board->intro);
        $this->assertSame('Governance and guidance.', Page::where('key', 'advisory-council')->value('intro'));

        $menu = MenuItem::where('label', 'Board of Directors')->firstOrFail();
        $this->assertSame('/who-we-are/board-of-directors', $menu->url);
        $this->assertFalse(MenuItem::where('label', 'Board of Governance')->exists());
    }

    public function test_the_rename_migration_keeps_wording_an_editor_has_chosen(): void
    {
        Page::where('key', 'board')->update(['title' => 'Trustees', 'slug' => 'trustees']);

        (include database_path('migrations/2026_09_29_000002_rename_board_of_governance_to_directors.php'))->up();

        $board = Page::where('key', 'board')->firstOrFail();
        $this->assertSame('Trustees', $board->title);
        $this->assertSame('trustees', $board->slug);
    }

    /* ------------------------------------------------ Renaming never orphans --- */

    public function test_renaming_a_built_in_page_never_loses_it(): void
    {
        // What an editor does to rename a page: new title, new heading, new slug.
        Page::where('key', 'board')->update([
            'title' => 'Directors Circle',
            'slug' => 'directors-circle',
            'hero_title' => 'Meet the Directors',
        ]);

        $this->get('/who-we-are/board-of-directors')
            ->assertOk()
            ->assertSee('Directors Circle')
            ->assertSee('Meet the Directors')
            // The fallback for a missing page is a title built from the key.
            ->assertDontSee('Board Of Governance');
    }

    public function test_a_page_saved_before_keys_existed_is_still_found_by_its_slug(): void
    {
        Page::where('key', 'contact')->update(['key' => null, 'hero_title' => 'Reach the team']);

        $this->get('/contact')->assertOk()->assertSee('Reach the team');
    }

    public function test_the_site_keeps_working_before_the_database_update_is_applied(): void
    {
        // Simulate the moment between uploading new files and running the installer:
        // new code, but a database that has no `key` column yet.
        Schema::table('pages', fn (Blueprint $table) => $table->dropUnique(['key']));
        Schema::table('pages', fn (Blueprint $table) => $table->dropColumn('key'));

        $this->get('/')->assertOk();
        $this->get('/contact')->assertOk()->assertSee('Contact');
        $this->get('/who-we-are/vision-mission')->assertOk();
        $this->assertSame('EPIC Team', TeamMember::categories()['team']);
    }

    public function test_keys_are_added_to_an_existing_database(): void
    {
        Page::query()->update(['key' => null]);
        // The board page after an editor renamed it and picked their own slug.
        Page::where('slug', 'board-of-directors')->update(['slug' => 'our-directors', 'title' => 'Board of Directors']);

        (include database_path('migrations/2026_09_29_000001_add_key_to_pages_table.php'))->backfill();

        $this->assertSame('board', Page::where('slug', 'our-directors')->value('key'));
        $this->assertSame('home', Page::where('slug', 'home')->value('key'));
        $this->assertSame('advisory-council', Page::where('slug', 'advisory-council')->value('key'));
        // Pages reached through /p/{slug} have no key: their slug is theirs to edit.
        $this->assertNull(Page::where('slug', 'privacy-policy')->value('key'));
    }

    public function test_reseeding_does_not_recreate_a_renamed_page(): void
    {
        Page::where('key', 'board')->update(['slug' => 'our-directors', 'title' => 'Our Directors']);
        $before = Page::count();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($before, Page::count());
        $this->assertSame('Our Directors', Page::where('key', 'board')->value('title'));
    }

    /* ------------------------------------------------------ The dashboard --- */

    public function test_the_slug_of_a_built_in_page_is_read_only(): void
    {
        $page = Page::where('key', 'board')->firstOrFail();

        $html = $this->actingAs($this->admin)->get('/admin/pages/'.$page->id.'/edit')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<input[^>]*name="slug"[^>]*readonly/', $html);

        // A doctored request cannot change it either; the rest of the page still saves.
        $this->put('/admin/pages/'.$page->id, [
            'title' => 'Board of Trustees',
            'slug' => 'something-else',
            'sort' => 1,
            'is_published' => '1',
        ])->assertRedirect('/admin/pages');

        $page->refresh();
        $this->assertSame('Board of Trustees', $page->title);
        $this->assertSame('board-of-directors', $page->slug);
    }

    public function test_the_slug_of_a_page_an_editor_created_can_be_edited(): void
    {
        $this->actingAs($this->admin)->post('/admin/pages', [
            'title' => 'Our Story',
            'slug' => 'our-story',
            'sort' => 50,
            'is_published' => '1',
        ])->assertRedirect('/admin/pages');

        $page = Page::where('slug', 'our-story')->firstOrFail();
        $this->assertNull($page->key);

        $html = $this->get('/admin/pages/'.$page->id.'/edit')->getContent();
        $this->assertDoesNotMatchRegularExpression('/<input[^>]*name="slug"[^>]*readonly/', $html);

        $this->put('/admin/pages/'.$page->id, [
            'title' => 'Our Story',
            'slug' => 'about-our-story',
            'sort' => 50,
            'is_published' => '1',
        ])->assertRedirect('/admin/pages');

        $this->assertSame('about-our-story', $page->refresh()->slug);
    }

    public function test_team_groups_take_their_names_from_their_pages(): void
    {
        $this->assertSame('Board of Directors', TeamMember::categories()['board']);

        Page::where('key', 'board')->update(['title' => 'Trustees']);

        $this->assertSame('Trustees', TeamMember::categories()['board']);
        $this->actingAs($this->admin)->get('/admin/team-members/create')->assertOk()->assertSee('Trustees');
    }
}
