<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\GalleryAlbum;
use App\Models\ImageSetting;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Publication;
use App\Models\User;
use App\Support\Pictures;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Every picture can be fitted, cropped and zoomed from the dashboard. The choices
 * are stored against the picture's path and reach the page as CSS custom
 * properties, so they apply wherever the picture is shown.
 */
class PictureFitTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    /** @var list<string> */
    protected array $created = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('role', 'super_admin')->firstOrFail();
        Pictures::flush();
    }

    protected function tearDown(): void
    {
        foreach ($this->created as $path) {
            @unlink(public_path($path));
        }

        parent::tearDown();
    }

    protected function track(?string $path): ?string
    {
        if ($path) {
            $this->created[] = $path;
        }

        return $path;
    }

    protected function createPublication(string $title, array $extra = []): Publication
    {
        $this->actingAs($this->admin)->post('/admin/publications', array_merge([
            'title' => $title,
            'type' => 'Policy Brief',
            'collection' => 'collection',
            'cover_image' => UploadedFile::fake()->image('cover.jpg', 900, 1200),
            'is_published' => '1',
            'sort' => 0,
        ], $extra))->assertRedirect('/admin/publications');

        $publication = Publication::where('title', $title)->firstOrFail();
        $this->track($publication->cover_image);

        return $publication;
    }

    /* -------------------------------------------------------- The rules --- */

    public function test_a_picture_with_no_choices_adds_nothing_to_the_page(): void
    {
        $this->assertSame('', Pictures::declarations('uploads/x/none.jpg'));
        $this->assertSame('', pic_style('uploads/x/none.jpg'));
        $this->assertSame('', pic_style(null));
        $this->assertFalse(pic_adjusted('uploads/x/none.jpg'));
    }

    public function test_choices_become_css_custom_properties(): void
    {
        Pictures::save('uploads/x/a.jpg', ['fit' => 'cover', 'x' => 30, 'y' => 60, 'zoom' => 140]);

        $this->assertSame('--fit:cover;--pos:30% 60%;--zoom:1.4', Pictures::declarations('uploads/x/a.jpg'));
        $this->assertSame(' style="--fit:cover;--pos:30% 60%;--zoom:1.4"', pic_style('uploads/x/a.jpg'));
        $this->assertTrue(pic_adjusted('uploads/x/a.jpg'));
    }

    public function test_only_what_was_changed_is_written(): void
    {
        Pictures::save('uploads/x/b.jpg', ['fit' => 'contain']);

        $this->assertSame('--fit:contain', Pictures::declarations('uploads/x/b.jpg'));
    }

    public function test_declarations_merge_with_styles_a_template_already_needs(): void
    {
        Pictures::save('uploads/x/c.jpg', ['fit' => 'fill']);

        $this->assertSame(' style="max-height:50px;--fit:fill"', pic_style('uploads/x/c.jpg', 'max-height:50px;'));
        $this->assertSame(' style="max-height:50px"', pic_style('uploads/x/none.jpg', 'max-height:50px'));
    }

    public function test_out_of_range_values_are_clamped(): void
    {
        Pictures::save('uploads/x/d.jpg', ['fit' => 'cover', 'x' => 250, 'y' => -40, 'zoom' => 9999]);

        $row = ImageSetting::where('path', 'uploads/x/d.jpg')->firstOrFail();
        $this->assertSame([100, 0, 400], [$row->focus_x, $row->focus_y, $row->zoom]);

        Pictures::save('uploads/x/d.jpg', ['fit' => 'cover', 'zoom' => 20]);
        $this->assertSame(100, ImageSetting::where('path', 'uploads/x/d.jpg')->value('zoom'));
    }

    public function test_an_unknown_fit_is_ignored_and_can_never_reach_the_page(): void
    {
        Pictures::save('uploads/x/e.jpg', ['fit' => 'cover;}body{display:none', 'zoom' => 150]);

        $this->assertNull(ImageSetting::where('path', 'uploads/x/e.jpg')->value('fit'));
        $this->assertStringNotContainsString('display', Pictures::declarations('uploads/x/e.jpg'));

        // Even a tampered row cannot inject anything.
        ImageSetting::where('path', 'uploads/x/e.jpg')->update(['fit' => 'x;}body{display:none']);
        Pictures::flush();
        $this->assertStringNotContainsString('display', Pictures::declarations('uploads/x/e.jpg'));
    }

    public function test_choosing_the_defaults_again_removes_the_row(): void
    {
        Pictures::save('uploads/x/f.jpg', ['fit' => 'cover', 'zoom' => 200]);
        $this->assertDatabaseHas('image_settings', ['path' => 'uploads/x/f.jpg']);

        Pictures::save('uploads/x/f.jpg', ['fit' => 'auto', 'x' => 50, 'y' => 50, 'zoom' => 100]);
        $this->assertDatabaseMissing('image_settings', ['path' => 'uploads/x/f.jpg']);
    }

    public function test_external_pictures_cannot_be_adjusted(): void
    {
        Pictures::save('https://example.com/a.jpg', ['fit' => 'cover']);

        $this->assertSame(0, ImageSetting::count());
        $this->assertSame('', Pictures::declarations('https://example.com/a.jpg'));
    }

    /* ------------------------------------------------- The dashboard form --- */

    public function test_every_image_field_offers_the_fit_and_crop_controls(): void
    {
        $page = Page::where('key', 'home')->firstOrFail();

        $this->actingAs($this->admin)
            ->get('/admin/pages/'.$page->id.'/edit')
            ->assertOk()
            ->assertSee('data-pic-adjust="hero_image"', false)
            ->assertSee('How the picture fits')
            ->assertSee('Fill the frame, crop edges')
            ->assertSee('Whole picture, fit inside')
            ->assertSee('name="pic[hero_image][zoom]"', false);

        $this->get('/admin/publications/create')->assertOk()->assertSee('data-pic-adjust="cover_image"', false);
        $this->get('/admin/events/create')->assertOk()->assertSee('data-pic-adjust="image"', false);
        $this->get('/admin/team-members/create')->assertOk()->assertSee('data-pic-adjust="photo"', false);
    }

    public function test_fields_with_no_frame_to_fit_do_not_offer_them(): void
    {
        $this->actingAs($this->admin);

        $this->get('/admin/partners/create')->assertOk()->assertDontSee('data-pic-adjust', false);
        $this->get('/admin/chapters/create')->assertOk()->assertDontSee('data-pic-adjust', false);
        $this->get('/admin/users/create')->assertOk()->assertDontSee('data-pic-adjust', false);
    }

    public function test_the_form_shows_the_saved_choices(): void
    {
        $publication = $this->createPublication('Saved Choices', [
            'pic' => ['cover_image' => ['fit' => 'cover', 'x' => 20, 'y' => 80, 'zoom' => 175]],
        ]);

        $html = $this->get('/admin/publications/'.$publication->slug.'/edit')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<option value="cover"\s+selected/', $html);
        $this->assertMatchesRegularExpression('/name="pic\[cover_image\]\[x\]"[^>]*value="20"/', $html);
        $this->assertMatchesRegularExpression('/name="pic\[cover_image\]\[zoom\]"[^>]*value="175"/', $html);
    }

    /* ------------------------------------------ Saving and showing them --- */

    public function test_choices_saved_with_a_new_upload_are_applied_on_the_website(): void
    {
        $publication = $this->createPublication('Fitted Cover', [
            'pic' => ['cover_image' => ['fit' => 'cover', 'x' => 25, 'y' => 75, 'zoom' => 130]],
        ]);

        $this->assertDatabaseHas('image_settings', [
            'path' => $publication->cover_image, 'fit' => 'cover', 'focus_x' => 25, 'focus_y' => 75, 'zoom' => 130,
        ]);

        $html = $this->get('/publications')->assertOk()->getContent();
        $this->assertStringContainsString(
            'src="'.asset($publication->cover_image).'" alt="Fitted Cover" loading="lazy" style="--fit:cover;--pos:25% 75%;--zoom:1.3"',
            $html
        );
    }

    public function test_choices_can_be_changed_without_uploading_the_picture_again(): void
    {
        $publication = $this->createPublication('Adjust Later');
        $path = $publication->cover_image;
        $this->assertDatabaseMissing('image_settings', ['path' => $path]);

        $this->put('/admin/publications/'.$publication->slug, [
            'title' => 'Adjust Later', 'slug' => $publication->slug, 'type' => 'Policy Brief',
            'collection' => 'collection', 'is_published' => '1', 'sort' => 0,
            'pic' => ['cover_image' => ['fit' => 'contain', 'x' => 50, 'y' => 10, 'zoom' => 100]],
        ])->assertRedirect('/admin/publications');

        $this->assertSame($path, $publication->fresh()->cover_image);
        $this->assertDatabaseHas('image_settings', ['path' => $path, 'fit' => 'contain', 'focus_y' => 10]);
    }

    public function test_replacing_a_picture_drops_the_old_choices_and_keeps_the_new_ones(): void
    {
        $publication = $this->createPublication('Replace Me', [
            'pic' => ['cover_image' => ['fit' => 'cover', 'zoom' => 200]],
        ]);
        $old = $publication->cover_image;

        $this->put('/admin/publications/'.$publication->slug, [
            'title' => 'Replace Me', 'slug' => $publication->slug, 'type' => 'Policy Brief',
            'collection' => 'collection', 'is_published' => '1', 'sort' => 0,
            'cover_image' => UploadedFile::fake()->image('new.jpg', 800, 800),
            'pic' => ['cover_image' => ['fit' => 'fill', 'x' => 40, 'y' => 40, 'zoom' => 100]],
        ])->assertRedirect('/admin/publications');

        $new = $this->track($publication->fresh()->cover_image);

        $this->assertNotSame($old, $new);
        $this->assertDatabaseMissing('image_settings', ['path' => $old]);
        $this->assertDatabaseHas('image_settings', ['path' => $new, 'fit' => 'fill', 'focus_x' => 40]);
    }

    public function test_removing_a_picture_removes_its_choices(): void
    {
        $publication = $this->createPublication('Remove Me', [
            'pic' => ['cover_image' => ['fit' => 'cover']],
        ]);
        $path = $publication->cover_image;

        $this->put('/admin/publications/'.$publication->slug, [
            'title' => 'Remove Me', 'slug' => $publication->slug, 'type' => 'Policy Brief',
            'collection' => 'collection', 'is_published' => '1', 'sort' => 0,
            'remove_cover_image' => '1',
            'pic' => ['cover_image' => ['fit' => 'cover']],
        ])->assertRedirect('/admin/publications');

        $this->assertNull($publication->fresh()->cover_image);
        $this->assertDatabaseMissing('image_settings', ['path' => $path]);
    }

    public function test_deleting_a_record_removes_its_pictures_choices(): void
    {
        $publication = $this->createPublication('Delete Me', [
            'pic' => ['cover_image' => ['fit' => 'cover']],
        ]);
        $path = $publication->cover_image;

        $this->delete('/admin/publications/'.$publication->slug)->assertRedirect('/admin/publications');

        $this->assertDatabaseMissing('image_settings', ['path' => $path]);
    }

    public function test_a_doctored_request_cannot_write_arbitrary_values(): void
    {
        $publication = $this->createPublication('Doctored', [
            'pic' => ['cover_image' => ['fit' => 'cover;}x{', 'x' => 'abc', 'y' => '9999', 'zoom' => '-5']],
        ]);

        $row = ImageSetting::where('path', $publication->cover_image)->first();
        // Nothing valid was chosen for fit or zoom, and y clamped to its maximum.
        $this->assertNotNull($row);
        $this->assertNull($row->fit);
        $this->assertSame([50, 100, 100], [$row->focus_x, $row->focus_y, $row->zoom]);
    }

    public function test_choices_for_a_field_with_no_frame_are_ignored(): void
    {
        $this->actingAs($this->admin)->post('/admin/partners', [
            'name' => 'Some Partner',
            'type' => 'partner',
            'logo' => UploadedFile::fake()->image('logo.png', 300, 100),
            'is_active' => '1',
            'sort' => 0,
            'pic' => ['logo' => ['fit' => 'cover', 'zoom' => 300]],
        ]);

        $this->assertSame(0, ImageSetting::count());
        foreach (Partner::all() as $partner) {
            $this->track($partner->logo);
        }
    }

    /* ------------------------------------------------------ Album photos --- */

    public function test_each_album_photo_can_be_fitted_on_its_own(): void
    {
        $album = GalleryAlbum::create(['title' => 'Field Visit', 'slug' => 'field-visit', 'is_published' => true, 'sort' => 1]);
        $first = $album->images()->create(['image' => 'uploads/gallery/one.jpg', 'sort' => 1]);
        $second = $album->images()->create(['image' => 'uploads/gallery/two.jpg', 'sort' => 2]);

        // The screen offers the controls once per photo, with unique element ids.
        $html = $this->actingAs($this->admin)->get('/admin/gallery-albums/field-visit/edit')->assertOk()->getContent();
        $this->assertSame(2, substr_count($html, 'data-pic-adjust="image"'));
        $this->assertStringContainsString('id="pic-image-'.$first->id.'-fit"', $html);
        $this->assertStringContainsString('id="pic-image-'.$second->id.'-fit"', $html);

        $this->put('/admin/gallery-albums/field-visit/images/'.$second->id, [
            'caption' => 'Second', 'sort' => 2,
            'pic' => ['image' => ['fit' => 'cover', 'x' => 10, 'y' => 90, 'zoom' => 120]],
        ])->assertRedirect();

        // Only that photo changed.
        $this->assertDatabaseHas('image_settings', ['path' => 'uploads/gallery/two.jpg', 'fit' => 'cover', 'focus_x' => 10]);
        $this->assertDatabaseMissing('image_settings', ['path' => 'uploads/gallery/one.jpg']);

        $page = $this->get('/media/gallery/field-visit')->assertOk();
        $page->assertSee('style="--fit:cover;--pos:10% 90%;--zoom:1.2"', false);
        $this->assertSame(1, substr_count($page->getContent(), '--fit:cover'));
    }

    public function test_removing_an_album_photo_removes_its_choices(): void
    {
        $album = GalleryAlbum::create(['title' => 'Field Visit', 'slug' => 'field-visit', 'is_published' => true, 'sort' => 1]);
        $photo = $album->images()->create(['image' => 'uploads/gallery/gone.jpg', 'sort' => 1]);
        Pictures::save('uploads/gallery/gone.jpg', ['fit' => 'cover']);

        $this->actingAs($this->admin)->delete('/admin/gallery-albums/field-visit/images/'.$photo->id)->assertRedirect();

        $this->assertDatabaseMissing('image_settings', ['path' => 'uploads/gallery/gone.jpg']);
    }

    /* ------------------------------------------------------ Page headers --- */

    public function test_a_page_header_picture_is_a_real_image_that_can_be_fitted(): void
    {
        Page::where('key', 'contact')->update(['hero_image' => 'uploads/pages/header.jpg']);
        Pictures::save('uploads/pages/header.jpg', ['fit' => 'contain', 'x' => 70, 'y' => 30, 'zoom' => 120]);

        $html = $this->get('/contact')->assertOk()->getContent();

        $this->assertStringContainsString('class="page-hero-media"', $html);
        $this->assertStringContainsString('style="--fit:contain;--pos:70% 30%;--zoom:1.2"', $html);
        // The old approach could not be zoomed or repositioned.
        $this->assertStringNotContainsString("background-image:url('", $html);
    }

    public function test_a_page_header_without_a_picture_has_no_image_element(): void
    {
        $html = $this->get('/contact')->assertOk()->getContent();

        $this->assertStringNotContainsString('page-hero-media', $html);
    }

    /* ---------------------------------------------------- Detail pages ---- */

    public function test_a_detail_page_picture_keeps_its_natural_size_until_adjusted(): void
    {
        $event = Event::published()->firstOrFail();
        $event->update(['image' => 'uploads/events/detail.jpg']);

        $this->get('/events/'.$event->slug)->assertOk()
            ->assertSee('<div class="detail-picture ">', false)
            ->assertDontSee('is-framed', false);

        Pictures::save('uploads/events/detail.jpg', ['fit' => 'cover', 'zoom' => 150]);

        $this->get('/events/'.$event->slug)->assertOk()
            ->assertSee('detail-picture is-framed', false)
            ->assertSee('style="--fit:cover;--zoom:1.5"', false);
    }

    /* ---------------------------------------------------------- Defaults --- */

    public function test_the_automatic_choice_shows_the_whole_picture(): void
    {
        $css = file_get_contents(public_path('css/site.css'));

        // Content frames fall back to showing everything; page headers fill their frame.
        $this->assertStringContainsString('object-fit: var(--fit, contain);', $css);
        $this->assertStringContainsString('object-fit: var(--fit, cover);', $css);
        // No frame is hard-coded to crop any more.
        $this->assertStringNotContainsString('.card-media img { width: 100%; height: 100%; object-fit: cover', $css);
        $this->assertStringNotContainsString('.member-photo img { width: 100%; height: 100%; object-fit: cover', $css);
    }
}
