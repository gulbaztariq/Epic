@extends('layouts.admin')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')
@section('crumb', 'Overview of the EPIC website')

@section('content')
    <div class="page-head">
        <div>
            <h2 style="margin:0">Welcome back, {{ Str::before(auth()->user()->name, ' ') }}</h2>
            <p>Everything on the public website is managed from here. Pick a section on the left, or start with a shortcut below.</p>
        </div>
        <a class="btn btn-outline" href="{{ route('home') }}" target="_blank" rel="noopener">{!! icon('eye') !!} Open website</a>
    </div>

    @if ($unreadMessages || $unreadVolunteers)
        <div class="alert alert-info">
            {!! icon('inbox') !!}
            <div>
                You have
                @if ($unreadMessages)<a href="{{ route('admin.messages') }}"><strong>{{ $unreadMessages }}</strong> unread message{{ $unreadMessages === 1 ? '' : 's' }}</a>@endif
                @if ($unreadMessages && $unreadVolunteers) and @endif
                @if ($unreadVolunteers)<a href="{{ route('admin.volunteers') }}"><strong>{{ $unreadVolunteers }}</strong> new volunteer application{{ $unreadVolunteers === 1 ? '' : 's' }}</a>@endif.
            </div>
        </div>
    @endif

    {{-- Visitors at a glance --}}
    <div class="stat-row">
        <div class="stat">
            <span class="stat-label">Visitors today</span>
            <strong class="stat-value">{{ number_format($analytics['visitors_today']) }}</strong>
            <span class="stat-delta"><small>{{ number_format($analytics['views_today']) }} page views</small></span>
        </div>
        <div class="stat">
            <span class="stat-label">Visitors (30 days)</span>
            <strong class="stat-value">{{ number_format($analytics['visitors_month']) }}</strong>
            <span class="stat-delta"><small>{{ number_format($analytics['views_month']) }} page views</small></span>
        </div>
        <div class="stat" style="justify-content:center">
            <span class="stat-label">Visitor reports</span>
            <a class="btn btn-blue btn-sm" href="{{ route('admin.analytics.index') }}" style="align-self:flex-start;margin-top:6px">
                {!! icon('trending-up') !!} Open analytics
            </a>
        </div>
    </div>

    <div class="grid grid-4">
        @foreach ($tiles as $tile)
            <a class="tile {{ $tile['tone'] ?? '' }}" href="{{ route($tile['route']) }}">
                <span class="tile-icon">{!! icon($tile['icon']) !!}</span>
                <span>
                    <strong>{{ $tile['value'] }}</strong>
                    <span>{{ $tile['label'] }}</span>
                </span>
            </a>
        @endforeach
    </div>

    <div class="grid grid-2" style="margin-top:20px">
        <div class="card">
            <div class="card-head">
                <h3>Latest messages</h3>
                <a class="btn btn-outline btn-sm" href="{{ route('admin.messages') }}">View all</a>
            </div>
            <div class="table-wrap">
                <table class="data">
                    <tbody>
                        @forelse ($latestMessages as $message)
                            <tr>
                                <td>
                                    <a class="row-title" href="{{ route('admin.messages.show', $message) }}">{{ $message->name }}</a>
                                    <span class="row-sub">{{ Str::limit($message->subject ?: $message->message, 60) }}</span>
                                </td>
                                <td style="text-align:right;white-space:nowrap">
                                    @unless ($message->is_read)<span class="badge badge-amber">New</span>@endunless
                                    <span class="row-sub">{{ $message->created_at->diffForHumans() }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td><div class="empty">{!! icon('inbox') !!}<p>No messages yet.</p></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h3>Upcoming events</h3>
                <a class="btn btn-outline btn-sm" href="{{ route('admin.events.index') }}">Manage</a>
            </div>
            <div class="table-wrap">
                <table class="data">
                    <tbody>
                        @forelse ($upcomingEvents as $event)
                            <tr>
                                <td>
                                    <a class="row-title" href="{{ route('admin.events.edit', $event) }}">{{ $event->title }}</a>
                                    <span class="row-sub">{{ $event->city }} · {{ $event->mode }}</span>
                                </td>
                                <td style="text-align:right;white-space:nowrap">{{ $event->starts_at?->format('d M Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td><div class="empty">{!! icon('calendar') !!}<p>No upcoming events.</p>
                                <a class="btn btn-primary btn-sm" href="{{ route('admin.events.create') }}">Add an event</a></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="grid grid-2" style="margin-top:20px">
        <div class="card">
            <div class="card-head">
                <h3>Recent publications</h3>
                <a class="btn btn-outline btn-sm" href="{{ route('admin.publications.create') }}">{!! icon('plus') !!} Add</a>
            </div>
            <div class="table-wrap">
                <table class="data">
                    <tbody>
                        @forelse ($recentPublications as $publication)
                            <tr>
                                <td>
                                    <a class="row-title" href="{{ route('admin.publications.edit', $publication) }}">{{ $publication->title }}</a>
                                    <span class="row-sub">{{ $publication->type }}</span>
                                </td>
                                <td style="text-align:right">
                                    <span class="badge {{ $publication->is_published ? 'badge-green' : 'badge-grey' }}">{{ $publication->is_published ? 'Published' : 'Draft' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td><div class="empty">{!! icon('book') !!}<p>No publications yet.</p></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3>Edit page content</h3></div>
            <div class="card-body">
                <p style="font-size:.88rem;color:var(--muted)">Headings, hero text and intro copy for each page of the website.</p>
                <div style="display:flex;flex-wrap:wrap;gap:8px">
                    @foreach ($pages as $page)
                        <a class="btn btn-outline btn-sm" href="{{ route('admin.pages.edit', $page) }}">{{ $page->title }}</a>
                    @endforeach
                    <a class="btn btn-blue btn-sm" href="{{ route('admin.pages.index') }}">All pages {!! icon('arrow-right') !!}</a>
                </div>
            </div>
        </div>
    </div>
@endsection
