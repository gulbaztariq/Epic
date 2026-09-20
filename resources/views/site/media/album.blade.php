@extends('layouts.site')

@section('title', $album->title.' | Gallery | '.setting('site_name', 'EPIC'))
@section('meta_description', summarise($album->description, 155))

@section('content')
    <x-page-hero
        :title="$album->title"
        :subtitle="$album->description"
        :eyebrow="$album->event_date?->format('d F Y')"
        :breadcrumbs="['Media' => null, 'Gallery' => route('media.gallery'), $album->title => null]" />

    <section class="section">
        <div class="container">
            @if ($album->images->isNotEmpty())
                <div class="gallery-grid">
                    @foreach ($album->images as $image)
                        <figure class="gallery-item" data-lightbox="{{ uploaded_url($image->image) }}" style="cursor:zoom-in">
                            <img src="{{ uploaded_url($image->image) }}" alt="{{ $image->caption ?: $album->title }}" loading="lazy">
                            @if ($image->caption)<figcaption>{{ $image->caption }}</figcaption>@endif
                        </figure>
                    @endforeach
                </div>
            @else
                <x-empty-state title="Photos coming soon" icon="image" />
            @endif

            @if ($more->isNotEmpty())
                <div style="margin-top:56px">
                    <h2 class="section-title" style="font-size:1.4rem">More albums</h2>
                    <div class="grid grid-3">
                        @foreach ($more as $item)
                            <article class="card">
                                <a class="card-media is-wide" href="{{ route('media.gallery.show', $item->slug) }}">
                                    <img src="{{ epic_image($item->cover, 'card') }}" alt="{{ $item->title }}" loading="lazy">
                                </a>
                                <div class="card-body"><h3><a href="{{ route('media.gallery.show', $item->slug) }}">{{ $item->title }}</a></h3></div>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
