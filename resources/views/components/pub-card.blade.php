@props(['publication'])

<article class="card pub-card">
    <a class="card-media" href="{{ route('publications.show', $publication->slug) }}">
        <img src="{{ epic_image($publication->cover_image, 'portrait') }}" alt="{{ $publication->title }}" loading="lazy">
    </a>
    <div class="card-body">
        <h3><a href="{{ route('publications.show', $publication->slug) }}">{{ $publication->title }}</a></h3>
        @if ($publication->subtitle)
            <p class="pub-sub">{{ $publication->subtitle }}</p>
        @endif
        <div class="card-meta">
            <span class="badge badge-blue">{{ $publication->type }}</span>
            @if ($publication->published_at)
                <span>{!! icon('calendar') !!}{{ $publication->published_at->format('M Y') }}</span>
            @endif
        </div>
    </div>
</article>
