@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title).' | '.setting('site_name', 'EPIC'))
@section('meta_description', $page->meta_description ?: summarise($page->intro, 155))

@section('content')
    <x-page-hero :page="$page" :breadcrumbs="['Media' => null, $page->title => null]" />

    <section class="section">
        <div class="container">
            @if ($page->intro)<p class="lead" style="max-width:76ch">{{ $page->intro }}</p>@endif

            @if ($albums->count())
                <div class="grid grid-3 mt-4">
                    @foreach ($albums as $album)
                        <article class="card">
                            <a class="card-media is-wide" href="{{ route('media.gallery.show', $album->slug) }}">
                                <img src="{{ epic_image($album->cover, 'card') }}" alt="{{ $album->title }}" loading="lazy">
                                <span class="badge badge-navy">{{ $album->images->count() }} photos</span>
                            </a>
                            <div class="card-body">
                                <h3><a href="{{ route('media.gallery.show', $album->slug) }}">{{ $album->title }}</a></h3>
                                @if ($album->description)<p>{{ summarise($album->description, 110) }}</p>@endif
                                <div class="card-meta">
                                    @if ($album->event_date)<span>{!! icon('calendar') !!}{{ $album->event_date->format('d M Y') }}</span>@endif
                                    @if ($album->location)<span>{!! icon('location') !!}{{ $album->location }}</span>@endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
                {{ $albums->links('vendor.pagination.epic') }}
            @else
                <x-empty-state title="Photo albums coming soon" icon="image">
                    <p style="margin:0">Photographs from EPIC dialogues, workshops, launches and field work.</p>
                </x-empty-state>
            @endif
        </div>
    </section>

    @include('partials.sections')
@endsection
