<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\GalleryAlbum;
use App\Models\GalleryImage;
use App\Models\ListItem;
use App\Models\Page;
use App\Models\Publication;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected array $createdFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('role', 'super_admin')->firstOrFail();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $path) {
            @unlink(public_path($path));
        }

        parent::tearDown();
    }

    protected function track(?string $path): ?string
    {
        if ($path) {
            $this->createdFiles[] = $path;
        }

        return $path;
    }

    /* ----------------------------------------------------------- Auth --- */

    public function test_guests_are_redirected_to_the_login_screen(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/publications')->assertRedirect('/admin/login');
    }

    public function test_super_admin_can_sign_in(): void
    {
        $this->post('/admin/login', [
            'email' => 'admin@epic.org.pk',
            'password' => 'EpicAdmin@2025',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($this->admin);
        $this->assertNotNull($this->admin->fresh()->last_login_at);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->post('/admin/login', ['email' => 'admin@epic.org.pk', 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_disabled_accounts_cannot_sign_in(): void
    {
        $user = User::factory()->create(['password' => 'secret12345', 'role' => 'admin', 'is_active' => false]);

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'secret12345'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_sign_out_works(): void
    {
        $this->actingAs($this->admin)->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    /* -------------------------------------------------------- Screens --- */

    public static function adminScreens(): array
    {
        return array_map(fn ($uri) => [$uri], [
            '/admin',
            '/admin/pages', '/admin/pages/create',
            '/admin/page-sections', '/admin/page-sections/create',
            '/admin/menu-items', '/admin/menu-items/create',
            '/admin/focus-areas', '/admin/focus-areas/create',
            '/admin/list-items', '/admin/list-items/create',
            '/admin/stats', '/admin/stats/create',
            '/admin/publications', '/admin/publications/create',
            '/admin/events', '/admin/events/create',
            '/admin/posts', '/admin/posts/create',
            '/admin/team-members', '/admin/team-members/create',
            '/admin/projects', '/admin/projects/create',
            '/admin/chapters', '/admin/chapters/create',
            '/admin/partners', '/admin/partners/create',
            '/admin/careers', '/admin/careers/create',
            '/admin/podcasts', '/admin/podcasts/create',
            '/admin/videos', '/admin/videos/create',
            '/admin/gallery-albums', '/admin/gallery-albums/create',
            '/admin/users', '/admin/users/create',
            '/admin/settings/general', '/admin/settings/contact', '/admin/settings/social',
            '/admin/settings/footer', '/admin/settings/home', '/admin/settings/integrations',
            '/admin/media-library', '/admin/messages', '/admin/volunteers',
            '/admin/subscribers', '/admin/profile',
        ]);
    }

    #[DataProvider('adminScreens')]
    public function test_admin_screens_load(string $uri): void
    {
        $this->actingAs($this->admin)->get($uri)->assertOk();
    }

    public function test_edit_screens_load_for_seeded_records(): void
    {
        $this->actingAs($this->admin);

        $this->get('/admin/pages/'.Page::first()->id.'/edit')->assertOk();
        $this->get('/admin/publications/pakistans-growth-opportunity/edit')->assertOk();
        $this->get('/admin/events/pakistans-economic-reform-agenda/edit')->assertOk();
        $this->get('/admin/list-items/'.ListItem::first()->id.'/edit')->assertOk();
    }

    /* ----------------------------------------------------------- CRUD --- */

    public function test_a_publication_can_be_created_with_a_cover_image(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/publications', [
                'title' => 'Labour Market Outlook 2026',
                'subtitle' => 'Jobs, skills and productivity',
                'type' => 'Policy Brief',
                'collection' => 'collection',
                'abstract' => 'A short abstract.',
                'cover_image' => UploadedFile::fake()->image('cover.jpg', 900, 1200),
                'is_published' => '1',
                'sort' => 0,
            ])
            ->assertRedirect('/admin/publications');

        $publication = Publication::where('title', 'Labour Market Outlook 2026')->first();

        $this->assertNotNull($publication);
        $this->assertSame('labour-market-outlook-2026', $publication->slug);
        $this->assertStringStartsWith('uploads/publications/', $this->track($publication->cover_image));
        $this->assertFileExists(public_path($publication->cover_image));
        $this->assertDatabaseHas('media_files', ['path' => $publication->cover_image]);

        // And it is live on the public website.
        $this->get('/publications/labour-market-outlook-2026')->assertOk()->assertSee('Labour Market Outlook 2026');
    }

    public function test_updating_a_publication_replaces_the_old_image(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/publications', [
            'title' => 'First Title',
            'type' => 'Working Paper',
            'collection' => 'collection',
            'cover_image' => UploadedFile::fake()->image('first.jpg'),
            'is_published' => '1',
            'sort' => 0,
        ]);

        $publication = Publication::where('title', 'First Title')->firstOrFail();
        $originalImage = $publication->cover_image;

        $this->put('/admin/publications/'.$publication->slug, [
            'title' => 'Updated Title',
            'slug' => $publication->slug,
            'type' => 'Working Paper',
            'collection' => 'collection',
            'cover_image' => UploadedFile::fake()->image('second.jpg'),
            'is_published' => '1',
            'sort' => 0,
        ])->assertRedirect('/admin/publications');

        $publication->refresh();

        $this->assertSame('Updated Title', $publication->title);
        $this->assertNotSame($originalImage, $this->track($publication->cover_image));
        $this->assertFileDoesNotExist(public_path($originalImage));
        $this->assertFileExists(public_path($publication->cover_image));
    }

    public function test_an_image_can_be_removed_from_a_record(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/publications', [
            'title' => 'With Cover',
            'type' => 'Policy Brief',
            'collection' => 'collection',
            'cover_image' => UploadedFile::fake()->image('cover.jpg'),
            'is_published' => '1',
            'sort' => 0,
        ]);

        $publication = Publication::where('title', 'With Cover')->firstOrFail();
        $image = $publication->cover_image;

        $this->put('/admin/publications/'.$publication->slug, [
            'title' => 'With Cover',
            'slug' => $publication->slug,
            'type' => 'Policy Brief',
            'collection' => 'collection',
            'remove_cover_image' => '1',
            'is_published' => '1',
            'sort' => 0,
        ])->assertRedirect();

        $this->assertNull($publication->fresh()->cover_image);
        $this->assertFileDoesNotExist(public_path($image));
    }

    public function test_deleting_a_record_also_deletes_its_upload(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/publications', [
            'title' => 'Temporary Paper',
            'type' => 'Working Paper',
            'collection' => 'collection',
            'cover_image' => UploadedFile::fake()->image('temp.jpg'),
            'is_published' => '1',
            'sort' => 0,
        ]);

        $publication = Publication::where('title', 'Temporary Paper')->firstOrFail();
        $image = $publication->cover_image;

        $this->delete('/admin/publications/'.$publication->slug)->assertRedirect('/admin/publications');

        $this->assertDatabaseMissing('publications', ['id' => $publication->id]);
        $this->assertFileDoesNotExist(public_path($image));
    }

    public function test_validation_errors_are_returned_for_invalid_input(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/publications', ['title' => '', 'type' => '', 'collection' => ''])
            ->assertSessionHasErrors(['title', 'type', 'collection']);
    }

    /* ------------------------------------------------------- Settings --- */

    public function test_settings_can_be_updated_including_the_logo(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/settings/general', [
                'site_name' => 'EPIC Pakistan',
                'site_name_full' => 'Economic Policy and Innovation Centre',
                'site_description' => 'Updated description',
                'header_cta_label' => 'Support Our Work',
                'header_cta_url' => '/contact',
                'logo' => UploadedFile::fake()->image('logo.png', 600, 300),
            ])
            ->assertRedirect('/admin/settings/general');

        Setting::flush();

        $this->assertSame('EPIC Pakistan', Setting::get('site_name'));
        $logo = $this->track(Setting::get('logo'));
        $this->assertFileExists(public_path($logo));

        // The new site name is used by the public website.
        $this->get('/')->assertOk()->assertSee('EPIC Pakistan', false);
    }

    /* -------------------------------------------------------- Gallery --- */

    public function test_photos_can_be_added_to_and_removed_from_an_album(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/gallery-albums', [
            'title' => 'Policy Dialogue 2026',
            'is_published' => '1',
            'sort' => 0,
        ])->assertRedirect();

        $album = GalleryAlbum::where('title', 'Policy Dialogue 2026')->firstOrFail();

        $this->post('/admin/gallery-albums/'.$album->slug.'/images', [
            'images' => [
                UploadedFile::fake()->image('one.jpg'),
                UploadedFile::fake()->image('two.jpg'),
            ],
        ])->assertRedirect();

        $this->assertSame(2, $album->images()->count());

        $image = $album->images()->first();
        $this->track($image->image);
        $this->assertFileExists(public_path($image->image));

        $this->get('/media/gallery')->assertOk()->assertSee('Policy Dialogue 2026');
        $this->get('/media/gallery/'.$album->slug)->assertOk();

        $this->delete('/admin/gallery-albums/'.$album->slug.'/images/'.$image->id)->assertRedirect();

        $this->assertSame(1, $album->images()->count());
        $this->assertFileDoesNotExist(public_path($image->image));

        foreach (GalleryImage::all() as $remaining) {
            $this->track($remaining->image);
        }
    }

    /* ----------------------------------------------------- Safeguards --- */

    public function test_an_admin_cannot_delete_their_own_account(): void
    {
        $this->actingAs($this->admin)
            ->delete('/admin/users/'.$this->admin->id)
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_the_last_super_admin_cannot_be_deleted(): void
    {
        $other = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($other)
            ->delete('/admin/users/'.$this->admin->id)
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_editors_cannot_reach_settings_or_user_management(): void
    {
        $editor = User::factory()->create(['role' => 'editor', 'is_active' => true]);

        $this->actingAs($editor)->get('/admin/settings/general')->assertForbidden();
        $this->actingAs($editor)->get('/admin/users')->assertForbidden();

        // but they can still manage content
        $this->actingAs($editor)->get('/admin/publications')->assertOk();
    }

    public function test_the_contact_inbox_marks_messages_as_read(): void
    {
        $this->post('/contact', [
            'name' => 'Sana Iqbal',
            'email' => 'sana@example.com',
            'message' => 'Hello EPIC',
        ]);

        $message = ContactMessage::firstOrFail();
        $this->assertFalse($message->is_read);

        $this->actingAs($this->admin)->get('/admin/messages/'.$message->id)->assertOk()->assertSee('Sana Iqbal');

        $this->assertTrue($message->fresh()->is_read);
    }

    public function test_subscribers_can_be_exported_as_csv(): void
    {
        $this->post('/subscribe', ['email' => 'export@example.com']);

        $this->actingAs($this->admin)
            ->get('/admin/subscribers/export')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_menu_changes_appear_on_the_website(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/menu-items', [
                'label' => 'Impact Stories',
                'url' => '/blogs-and-articles',
                'location' => 'header',
                'target' => '_self',
                'sort' => 99,
                'is_active' => '1',
            ])->assertRedirect('/admin/menu-items');

        $this->get('/')->assertOk()->assertSee('Impact Stories');
    }
}
