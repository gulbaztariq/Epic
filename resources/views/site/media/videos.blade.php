@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title).' | '.setting('site_name', 'EPIC'))
@section('meta_description', $page->meta_description ?: summarise($page->intro, 155))

@section('content')
    <x-page-hero :page="$page" :breadcrumbs="['Media' => null, $page->title => null]" />

    <section class="section">
        <div class="container">
            @if ($page->intro)<p class="lead" style="max-width:76ch">{{ $page->intro }}</p>@endif

            @if ($featured && $featured->embed_url)
                <div class="video-embed mt-4" style="margin-bottom:34px">
                    <iframe src="{{ $featured->embed_url }}" title="{{ $featured->title }}" loading="lazy"
                            allow="accelerometer; clipboard-write; encrypted-media; picture-in-picture" allowfullscreen></iframe>
                </div>
                <h2 class="section-title" style="font-size:1.35rem">{{ $featured->title }}</h2>
                @if ($featured->description)<p style="max-width:76ch">{{ $featured->description }}</p>@endif
            @endif

            @if ($videos->count())
                <div class="grid grid-3" style="margin-top:34px">
                    @foreach ($videos as $video)
                        <article class="card">
                            <a class="video-thumb" href="{{ $video->watch_url }}" target="_blank" rel="noopener">
                                <img src="{{ $video->poster ?: epic_image(null, 'card') }}" alt="{{ $video->title }}" loading="lazy">
                                <span class="play">{!! icon('play') !!}</span>
                            </a>
                            <div class="card-body">
                                <h3 style="font-size:1.02rem"><a href="{{ $video->watch_url }}" target="_blank" rel="noopener">{{ $video->title }}</a></h3>
                                @if ($video->description)<p>{{ summarise($video->description, 110) }}</p>@endif
                                @if ($video->published_at)
                                    <div class="card-meta"><span>{!! icon('calendar') !!}{{ $video->published_at->format('d M Y') }}</span></div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
                {{ $videos->links('vendor.pagination.epic') }}
            @else
                <x-empty-state title="Videos coming soon" icon="youtube">
                    <p style="margin:0">Recordings of EPIC dialogues, seminars and expert conversations will be published here.</p>
                </x-empty-state>
            @endif
        </div>
    </section>

    @include('partials.sections')
@endsection
