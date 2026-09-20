<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Subscriber;
use App\Models\VolunteerApplication;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public static function publicRoutes(): array
    {
        return [
            ['/'],
            ['/who-we-are'],
            ['/who-we-are/vision-mission'],
            ['/who-we-are/epic-principles'],
            ['/who-we-are/our-strengths'],
            ['/who-we-are/epic-team'],
            ['/who-we-are/board-of-governance'],
            ['/who-we-are/advisory-council'],
            ['/what-we-do/themes'],
            ['/what-we-do/projects'],
            ['/what-we-do/international-chapters'],
            ['/events'],
            ['/events?show=past'],
            ['/partnerships'],
            ['/partnerships/mous'],
            ['/partnerships/memberships'],
            ['/publications'],
            ['/publications/journal'],
            ['/publications/e-newsletter'],
            ['/blogs-and-articles'],
            ['/get-involved/careers'],
            ['/get-involved/volunteer'],
            ['/get-involved/subscribe'],
            ['/contact'],
            ['/media/press-releases'],
            ['/media/podcast'],
            ['/media/youtube'],
            ['/media/gallery'],
            ['/search?q=human'],
            ['/p/privacy-policy'],
            ['/p/terms-of-use'],
            ['/sitemap.xml'],
        ];
    }

    #[DataProvider('publicRoutes')]
    public function test_public_pages_load(string $uri): void
    {
        $this->get($uri)->assertOk();
    }

    public function test_home_page_shows_seeded_content(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Opportunity.')
            ->assertSee('Our Focus Areas')
            ->assertSee('Economic Policy')
            ->assertSee('Featured Publications')
            ->assertSee('Data &amp; Insights', false);
    }

    public function test_detail_pages_load(): void
    {
        $this->get('/publications/pakistans-growth-opportunity')->assertOk()->assertSee('Growth Opportunity', false);
        $this->get('/events/pakistans-economic-reform-agenda')->assertOk();
        $this->get('/what-we-do/projects/skills-for-the-future-workforce')->assertOk();
    }

    public function test_unknown_page_returns_404(): void
    {
        $this->get('/no-such-page')->assertNotFound();
    }

    public function test_contact_form_stores_a_message(): void
    {
        $this->post('/contact', [
            'name' => 'Ayesha Khan',
            'email' => 'ayesha@example.com',
            'subject' => 'Research collaboration',
            'message' => 'We would like to explore a joint study.',
        ])->assertRedirect();

        $this->assertDatabaseHas('contact_messages', ['email' => 'ayesha@example.com']);
        $this->assertSame(1, ContactMessage::count());
    }

    public function test_contact_form_validates_required_fields(): void
    {
        $this->post('/contact', ['name' => '', 'email' => 'not-an-email', 'message' => ''])
            ->assertSessionHasErrors(['name', 'email', 'message']);

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_subscribe_form_stores_a_subscriber_without_duplicates(): void
    {
        $this->post('/subscribe', ['email' => 'reader@example.com'])->assertRedirect();
        $this->post('/subscribe', ['email' => 'reader@example.com', 'name' => 'Reader'])->assertRedirect();

        $this->assertSame(1, Subscriber::where('email', 'reader@example.com')->count());
        $this->assertSame('Reader', Subscriber::first()->name);
    }

    public function test_volunteer_form_stores_an_application_with_a_cv(): void
    {
        $this->post('/get-involved/volunteer', [
            'name' => 'Bilal Ahmed',
            'email' => 'bilal@example.com',
            'interest' => 'Research support',
            'cv' => UploadedFile::fake()->create('cv.pdf', 40, 'application/pdf'),
        ])->assertRedirect();

        $application = VolunteerApplication::first();

        $this->assertNotNull($application);
        $this->assertStringStartsWith('uploads/volunteers/', $application->cv_path);
        $this->assertFileExists(public_path($application->cv_path));

        @unlink(public_path($application->cv_path));
    }

    public function test_honeypot_blocks_spam_submissions(): void
    {
        $this->post('/contact', [
            'name' => 'Spam Bot',
            'email' => 'spam@example.com',
            'message' => 'Buy things',
            'website' => 'http://spam.example',
        ])->assertSessionHasErrors('website');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_search_finds_seeded_publications(): void
    {
        $this->get('/search?q=Digital')->assertOk()->assertSee('Digital Economy', false);
    }
}
