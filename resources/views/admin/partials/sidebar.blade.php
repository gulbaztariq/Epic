@php
    $unreadMessages = \App\Models\ContactMessage::where('is_read', false)->count();
    $unreadVolunteers = \App\Models\VolunteerApplication::where('is_read', false)->count();

    $nav = [
        'Overview' => [
            ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'grid', 'pattern' => 'admin.dashboard'],
        ],
        'Analytics' => [
            ['route' => 'admin.analytics.index', 'label' => 'Visitor overview', 'icon' => 'trending-up', 'pattern' => 'admin.analytics.index'],
            ['route' => 'admin.analytics.visitors', 'label' => 'Visitor log', 'icon' => 'people', 'pattern' => 'admin.analytics.visitors'],
        ],
        'Website content' => [
            ['route' => 'admin.pages.index', 'label' => 'Pages', 'icon' => 'document', 'pattern' => 'admin.pages.*'],
            ['route' => 'admin.page-sections.index', 'label' => 'Page sections', 'icon' => 'layers', 'pattern' => 'admin.page-sections.*'],
            ['route' => 'admin.menu-items.index', 'label' => 'Navigation menus', 'icon' => 'menu', 'pattern' => 'admin.menu-items.*'],
            ['route' => 'admin.focus-areas.index', 'label' => 'Focus areas', 'icon' => 'target', 'pattern' => 'admin.focus-areas.*'],
            ['route' => 'admin.list-items.index', 'label' => 'Content lists', 'icon' => 'list', 'pattern' => 'admin.list-items.*'],
            ['route' => 'admin.stats.index', 'label' => 'Data & insights', 'icon' => 'chart', 'pattern' => 'admin.stats.*'],
        ],
        'Research & activities' => [
            ['route' => 'admin.publications.index', 'label' => 'Publications', 'icon' => 'book', 'pattern' => 'admin.publications.*'],
            ['route' => 'admin.events.index', 'label' => 'Events', 'icon' => 'calendar', 'pattern' => 'admin.events.*'],
            ['route' => 'admin.projects.index', 'label' => 'Projects', 'icon' => 'briefcase', 'pattern' => 'admin.projects.*'],
            ['route' => 'admin.posts.index', 'label' => 'Blogs & press', 'icon' => 'edit', 'pattern' => 'admin.posts.*'],
        ],
        'People & partners' => [
            ['route' => 'admin.team-members.index', 'label' => 'Team & councils', 'icon' => 'users', 'pattern' => 'admin.team-members.*'],
            ['route' => 'admin.partners.index', 'label' => 'Partners & MoUs', 'icon' => 'partnership', 'pattern' => 'admin.partners.*'],
            ['route' => 'admin.chapters.index', 'label' => 'International chapters', 'icon' => 'globe', 'pattern' => 'admin.chapters.*'],
            ['route' => 'admin.careers.index', 'label' => 'Careers', 'icon' => 'flag', 'pattern' => 'admin.careers.*'],
        ],
        'Media' => [
            ['route' => 'admin.podcasts.index', 'label' => 'Podcast episodes', 'icon' => 'mic', 'pattern' => 'admin.podcasts.*'],
            ['route' => 'admin.videos.index', 'label' => 'Videos', 'icon' => 'youtube', 'pattern' => 'admin.videos.*'],
            ['route' => 'admin.gallery-albums.index', 'label' => 'Photo gallery', 'icon' => 'image', 'pattern' => 'admin.gallery-albums.*'],
            ['route' => 'admin.media.index', 'label' => 'Media library', 'icon' => 'upload', 'pattern' => 'admin.media.*'],
        ],
        'Engagement' => [
            ['route' => 'admin.messages', 'label' => 'Messages', 'icon' => 'inbox', 'pattern' => 'admin.messages*', 'count' => $unreadMessages],
            ['route' => 'admin.volunteers', 'label' => 'Volunteers', 'icon' => 'heart', 'pattern' => 'admin.volunteers*', 'count' => $unreadVolunteers],
            ['route' => 'admin.subscribers', 'label' => 'Subscribers', 'icon' => 'mail', 'pattern' => 'admin.subscribers*'],
        ],
        'System' => array_values(array_filter([
            auth()->user()->canManageSystem()
                ? ['route' => 'admin.settings', 'label' => 'Site settings', 'icon' => 'gear', 'pattern' => 'admin.settings*']
                : null,
            auth()->user()->canManageSystem()
                ? ['route' => 'admin.users.index', 'label' => 'Admin users', 'icon' => 'shield', 'pattern' => 'admin.users.*']
                : null,
            ['route' => 'admin.profile', 'label' => 'My profile', 'icon' => 'people', 'pattern' => 'admin.profile*'],
        ])),
    ];
@endphp

<aside class="sidebar">
    <div class="sidebar-brand">
        <a href="{{ route('admin.dashboard') }}"><img src="{{ site_logo(true) }}" alt="EPIC"></a>
    </div>

    <nav class="sidebar-nav">
        @foreach ($nav as $group => $items)
            <div class="sidebar-group">
                <h5>{{ $group }}</h5>
                @foreach ($items as $item)
                    @continue(! \Illuminate\Support\Facades\Route::has($item['route']))
                    <a class="{{ request()->routeIs($item['pattern']) ? 'is-active' : '' }}" href="{{ route($item['route']) }}">
                        {!! icon($item['icon']) !!}
                        <span>{{ $item['label'] }}</span>
                        @if (! empty($item['count']))<span class="count">{{ $item['count'] }}</span>@endif
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>
</aside>
