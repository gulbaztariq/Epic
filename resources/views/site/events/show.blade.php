@extends('layouts.site')

@section('title', $event->title.' | '.setting('site_name', 'EPIC'))
@section('meta_description', summarise($event->excerpt ?: $event->description, 155))
@section('og_image', epic_image($event->image, 'wide'))

@section('content')
    <x-page-hero
        :title="$event->title"
        :subtitle="$event->excerpt"
        :eyebrow="$event->event_type"
        :breadcrumbs="['Events' => route('events.index'), $event->title => null]" />

    <section class="section">
        <div class="container">
            <div class="content-layout">
                <div>
                    <img src="{{ epic_image($event->image, 'wide') }}" alt="{{ $event->title }}"
                         style="border-radius:var(--radius-lg);width:100%;margin-bottom:28px">

                    <div class="meta-row">
                        @if ($event->starts_at)
                            <span>{!! icon('calendar') !!}{{ $event->starts_at->format('l, d F Y') }}</span>
                            <span>{!! icon('clock') !!}{{ $event->starts_at->format('H:i') }}@if ($event->ends_at) &ndash; {{ $event->ends_at->format('H:i') }}@endif</span>
                        @endif
                        @if ($event->city || $event->location)
                            <span>{!! icon('location') !!}{{ $event->location ?: $event->city }}</span>
                        @endif
                        <span>{!! icon('people') !!}{{ $event->mode }}</span>
                    </div>

                    <div class="prose">{!! rich($event->description) !!}</div>

                    @if ($event->registration_url && $event->is_upcoming)
                        <a class="btn btn-green btn-lg mt-4" href="{{ $event->registration_url }}" target="_blank" rel="noopener">
                            Register to attend {!! icon('arrow-right') !!}
                        </a>
                    @endif
                </div>

                <aside class="sidebar">
                    <div class="sidebar-box">
                        <h4>Event details</h4>
                        <ul class="contact-lines" style="gap:14px">
                            @if ($event->starts_at)
                                <li>{!! icon('calendar') !!}<div><strong>Date</strong><span>{{ $event->starts_at->format('d M Y') }}</span></div></li>
                            @endif
                            @if ($event->location || $event->city)
                                <li>{!! icon('location') !!}<div><strong>Venue</strong><span>{{ $event->location ?: $event->city }}</span></div></li>
                            @endif
                            <li>{!! icon('monitor') !!}<div><strong>Format</strong><span>{{ $event->mode }}</span></div></li>
                            @if ($event->event_type)
                                <li>{!! icon('layers') !!}<div><strong>Type</strong><span>{{ $event->event_type }}</span></div></li>
                            @endif
                        </ul>
                        @if ($event->registration_url && $event->is_upcoming)
                            <a class="btn btn-primary btn-block mt-3" href="{{ $event->registration_url }}" target="_blank" rel="noopener">Register</a>
                        @else
                            <a class="btn btn-outline btn-block mt-3" href="{{ route('contact') }}">Enquire about this event</a>
                        @endif
                    </div>

                    @if ($related->isNotEmpty())
                        <div class="sidebar-box">
                            <h4>More events</h4>
                            <ul>
                                @foreach ($related as $item)
                                    <li><a href="{{ route('events.show', $item->slug) }}">{{ $item->title }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </section>
@endsection
