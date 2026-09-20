@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title).' | '.setting('site_name', 'EPIC'))
@section('meta_description', $page->meta_description ?: summarise($page->intro, 155))

@section('content')
    <x-page-hero :page="$page" :breadcrumbs="[$page->title => null]" />

    <section class="section">
        <div class="container">
            @if ($page->intro)
                <p class="lead" style="max-width:76ch">{{ $page->intro }}</p>
            @endif

            <div class="chip-row mt-4" style="margin-bottom:28px">
                <a class="chip {{ $filter === 'upcoming' ? 'is-active' : '' }}" href="{{ route('events.index') }}">
                    {!! icon('calendar') !!} Upcoming ({{ $upcomingCount }})
                </a>
                <a class="chip {{ $filter === 'past' ? 'is-active' : '' }}" href="{{ route('events.index', ['show' => 'past']) }}">
                    {!! icon('clock') !!} Past events ({{ $pastCount }})
                </a>
            </div>

            @if ($events->count())
                <div class="grid grid-3">
                    @foreach ($events as $event)
                        <x-event-card :event="$event" />
                    @endforeach
                </div>
                {{ $events->links('vendor.pagination.epic') }}
            @else
                <x-empty-state title="{{ $filter === 'past' ? 'No past events listed yet' : 'No upcoming events scheduled' }}" icon="calendar">
                    <p style="margin:0">Subscribe to hear first about EPIC policy dialogues, roundtables, seminars and webinars.</p>
                </x-empty-state>
            @endif

            @if ($eventTypes->isNotEmpty())
                <div style="margin-top:56px">
                    <h2 class="section-title" style="font-size:1.45rem">Our events include</h2>
                    <x-check-list :items="$eventTypes" :columns="3" />
                </div>
            @endif
        </div>
    </section>

    @include('partials.sections')

    <x-subscribe-band />
@endsection
