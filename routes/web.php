<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GalleryImageController;
use App\Http\Controllers\Admin\InboxController;
use App\Http\Controllers\Admin\MediaLibraryController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\Resources;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\EngagementController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PartnershipController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PublicationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\WorkController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

// Who We Are
Route::prefix('who-we-are')->name('about.')->group(function () {
    Route::get('/', [AboutController::class, 'index'])->name('index');
    Route::get('vision-mission', [AboutController::class, 'vision'])->name('vision');
    Route::get('epic-principles', [AboutController::class, 'principles'])->name('principles');
    Route::get('our-strengths', [AboutController::class, 'strengths'])->name('strengths');
    Route::get('epic-team', [AboutController::class, 'team'])->name('team');
    Route::get('board-of-governance', [AboutController::class, 'board'])->name('board');
    Route::get('advisory-council', [AboutController::class, 'advisory'])->name('advisory');
});

// What We Do
Route::prefix('what-we-do')->name('work.')->group(function () {
    Route::get('/', [WorkController::class, 'themes'])->name('index');
    Route::get('themes', [WorkController::class, 'themes'])->name('themes');
    Route::get('projects', [WorkController::class, 'projects'])->name('projects');
    Route::get('projects/{project:slug}', [WorkController::class, 'project'])->name('projects.show');
    Route::get('international-chapters', [WorkController::class, 'chapters'])->name('chapters');
});

// Events
Route::get('events', [EventController::class, 'index'])->name('events.index');
Route::get('events/{event:slug}', [EventController::class, 'show'])->name('events.show');

// Partnerships & MoUs
Route::prefix('partnerships')->name('partnerships.')->group(function () {
    Route::get('/', [PartnershipController::class, 'index'])->name('index');
    Route::get('mous', [PartnershipController::class, 'mous'])->name('mous');
    Route::get('memberships', [PartnershipController::class, 'memberships'])->name('memberships');
});

// Publications
Route::prefix('publications')->name('publications.')->group(function () {
    Route::get('/', [PublicationController::class, 'index'])->name('index');
    Route::get('journal', [PublicationController::class, 'journal'])->name('journal');
    Route::get('e-newsletter', [PublicationController::class, 'newsletter'])->name('newsletter');
    Route::get('{publication:slug}', [PublicationController::class, 'show'])->name('show');
});

Route::get('blogs-and-articles', [PostController::class, 'index'])->name('blogs.index');
Route::get('blogs-and-articles/{post:slug}', [PostController::class, 'show'])->name('blogs.show');

// Get Involved
Route::prefix('get-involved')->name('involved.')->group(function () {
    Route::get('careers', [EngagementController::class, 'careers'])->name('careers');
    Route::get('careers/{career:slug}', [EngagementController::class, 'career'])->name('careers.show');
    Route::get('volunteer', [EngagementController::class, 'volunteer'])->name('volunteer');
    Route::post('volunteer', [EngagementController::class, 'storeVolunteer'])->name('volunteer.store');
    Route::get('subscribe', [EngagementController::class, 'subscribe'])->name('subscribe');
});

Route::get('contact', [EngagementController::class, 'contact'])->name('contact');
Route::post('contact', [EngagementController::class, 'storeContact'])->name('contact.store');
Route::post('subscribe', [EngagementController::class, 'storeSubscriber'])->name('subscribe.store');

// Media
Route::prefix('media')->name('media.')->group(function () {
    Route::get('press-releases', [MediaController::class, 'press'])->name('press');
    Route::get('press-releases/{post:slug}', [MediaController::class, 'pressShow'])->name('press.show');
    Route::get('podcast', [MediaController::class, 'podcasts'])->name('podcast');
    Route::get('podcast/{podcast:slug}', [MediaController::class, 'podcast'])->name('podcast.show');
    Route::get('youtube', [MediaController::class, 'videos'])->name('youtube');
    Route::get('gallery', [MediaController::class, 'gallery'])->name('gallery');
    Route::get('gallery/{album:slug}', [MediaController::class, 'album'])->name('gallery.show');
});

Route::get('search', [SearchController::class, 'index'])->name('search');
Route::get('sitemap.xml', [PageController::class, 'sitemapXml'])->name('sitemap.xml');
Route::get('p/{page:slug}', [PageController::class, 'show'])->name('page');

/*
|--------------------------------------------------------------------------
| Admin dashboard
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.attempt');
    });

    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Site settings (restricted to super admins and administrators)
        Route::middleware('can:manage-system')->group(function () {
            Route::get('settings/{group?}', [SettingController::class, 'edit'])->name('settings');
            Route::put('settings/{group}', [SettingController::class, 'update'])->name('settings.update');
        });

        Route::get('media-library', [MediaLibraryController::class, 'index'])->name('media.index');
        Route::post('media-library', [MediaLibraryController::class, 'store'])->name('media.store');
        Route::delete('media-library/{media}', [MediaLibraryController::class, 'destroy'])->name('media.destroy');

        // Inbox
        Route::get('messages', [InboxController::class, 'messages'])->name('messages');
        Route::get('messages/{message}', [InboxController::class, 'showMessage'])->name('messages.show');
        Route::delete('messages/{message}', [InboxController::class, 'destroyMessage'])->name('messages.destroy');
        Route::get('volunteers', [InboxController::class, 'volunteers'])->name('volunteers');
        Route::get('volunteers/{volunteer}', [InboxController::class, 'showVolunteer'])->name('volunteers.show');
        Route::delete('volunteers/{volunteer}', [InboxController::class, 'destroyVolunteer'])->name('volunteers.destroy');
        Route::get('subscribers', [InboxController::class, 'subscribers'])->name('subscribers');
        Route::get('subscribers/export', [InboxController::class, 'exportSubscribers'])->name('subscribers.export');
        Route::delete('subscribers/{subscriber}', [InboxController::class, 'destroySubscriber'])->name('subscribers.destroy');

        // Account
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

        // Gallery images live inside their album
        Route::post('gallery-albums/{album}/images', [GalleryImageController::class, 'store'])->name('gallery-albums.images.store');
        Route::put('gallery-albums/{album}/images/{image}', [GalleryImageController::class, 'update'])->name('gallery-albums.images.update');
        Route::delete('gallery-albums/{album}/images/{image}', [GalleryImageController::class, 'destroy'])->name('gallery-albums.images.destroy');

        // Content resources (generic CRUD engine)
        $resources = [
            'pages' => Resources\PageController::class,
            'page-sections' => Resources\PageSectionController::class,
            'menu-items' => Resources\MenuItemController::class,
            'focus-areas' => Resources\FocusAreaController::class,
            'list-items' => Resources\ListItemController::class,
            'stats' => Resources\StatController::class,
            'publications' => Resources\PublicationController::class,
            'events' => Resources\EventController::class,
            'posts' => Resources\PostController::class,
            'team-members' => Resources\TeamMemberController::class,
            'projects' => Resources\ProjectController::class,
            'chapters' => Resources\ChapterController::class,
            'partners' => Resources\PartnerController::class,
            'careers' => Resources\CareerController::class,
            'podcasts' => Resources\PodcastController::class,
            'videos' => Resources\VideoController::class,
            'gallery-albums' => Resources\GalleryAlbumController::class,
            'users' => Resources\UserController::class,
        ];

        foreach ($resources as $uri => $controller) {
            $route = Route::resource($uri, $controller)->except(['show']);

            if ($uri === 'users') {
                $route->middleware('can:manage-system');
            }
        }
    });
});
