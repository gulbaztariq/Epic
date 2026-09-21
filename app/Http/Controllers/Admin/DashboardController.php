<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Event;
use App\Models\GalleryAlbum;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Post;
use App\Models\Project;
use App\Models\Publication;
use App\Models\Subscriber;
use App\Models\TeamMember;
use App\Models\Visit;
use App\Models\VolunteerApplication;
use App\Services\WebScheduler;

class DashboardController extends Controller
{
    public function index(WebScheduler $scheduler)
    {
        return view('admin.dashboard', [
            'schedule' => $scheduler->status(),
            'schedulerEnabled' => $scheduler->isEnabled(),
            'tiles' => [
                ['label' => 'Publications', 'value' => Publication::count(), 'icon' => 'book', 'route' => 'admin.publications.index'],
                ['label' => 'Events', 'value' => Event::count(), 'icon' => 'calendar', 'route' => 'admin.events.index', 'tone' => 'is-green'],
                ['label' => 'Projects', 'value' => Project::count(), 'icon' => 'briefcase', 'route' => 'admin.projects.index', 'tone' => 'is-navy'],
                ['label' => 'Blogs & press', 'value' => Post::count(), 'icon' => 'edit', 'route' => 'admin.posts.index'],
                ['label' => 'Team & councils', 'value' => TeamMember::count(), 'icon' => 'users', 'route' => 'admin.team-members.index', 'tone' => 'is-green'],
                ['label' => 'Partners & MoUs', 'value' => Partner::count(), 'icon' => 'partnership', 'route' => 'admin.partners.index', 'tone' => 'is-navy'],
                ['label' => 'Photo albums', 'value' => GalleryAlbum::count(), 'icon' => 'image', 'route' => 'admin.gallery-albums.index'],
                ['label' => 'Subscribers', 'value' => Subscriber::count(), 'icon' => 'mail', 'route' => 'admin.subscribers', 'tone' => 'is-green'],
            ],
            'analytics' => [
                'views_today' => Visit::humans()->whereDate('visited_at', today())->count(),
                'visitors_today' => Visit::humans()->whereDate('visited_at', today())->distinct('visitor_key')->count('visitor_key'),
                'views_month' => Visit::humans()->where('visited_at', '>=', today()->subDays(29))->count(),
                'visitors_month' => Visit::humans()->where('visited_at', '>=', today()->subDays(29))->distinct('visitor_key')->count('visitor_key'),
            ],
            'unreadMessages' => ContactMessage::where('is_read', false)->count(),
            'unreadVolunteers' => VolunteerApplication::where('is_read', false)->count(),
            'latestMessages' => ContactMessage::latest()->take(5)->get(),
            'upcomingEvents' => Event::upcoming()->take(5)->get(),
            'recentPublications' => Publication::latest('id')->take(5)->get(),
            'pages' => Page::orderBy('sort')->take(8)->get(),
        ]);
    }
}
