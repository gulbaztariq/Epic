@props(['event'])

<article class="card event-card">
    <a class="event-thumb" href="{{ route('events.show', $event->slug) }}">
        <img src="{{ epic_image($event->image, 'card') }}" alt="{{ $event->title }}" loading="lazy">
    </a>
    <div class="event-main">
        <div class="event-date">
            <strong>{{ $event->day }}</strong>
            <span>{{ $event->month_year }}</span>
        </div>
        <div>
            <h3><a href="{{ route('events.show', $event->slug) }}">{{ $event->title }}</a></h3>
            @if ($event->excerpt)
                <p>{{ summarise($event->excerpt, 110) }}</p>
            @endif
            <div class="card-meta">
                @if ($event->city || $event->location)
                    <span>{!! icon('location') !!}{{ $event->city ?: $event->location }}</span>
                @endif
                <span>{!! icon('clock') !!}{{ $event->mode }}</span>
            </div>
        </div>
    </div>
</article>
