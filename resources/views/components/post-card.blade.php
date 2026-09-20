@props(['post'])

<article class="card">
    <a class="card-media is-wide" href="{{ $post->publicUrl() }}">
        <img src="{{ epic_image($post->image, 'card') }}" alt="{{ $post->title }}" loading="lazy">
        <span class="badge badge-navy">{{ $post->category_label }}</span>
    </a>
    <div class="card-body">
        <h3><a href="{{ $post->publicUrl() }}">{{ $post->title }}</a></h3>
        <p>{{ summarise($post->summary, 130) }}</p>
        <div class="card-meta">
            @if ($post->published_at)
                <span>{!! icon('calendar') !!}{{ $post->published_at->format('d M Y') }}</span>
            @endif
            @if ($post->author)
                <span>{!! icon('people') !!}{{ $post->author }}</span>
            @endif
        </div>
    </div>
</article>
