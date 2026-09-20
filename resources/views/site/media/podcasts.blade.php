@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title).' | '.setting('site_name', 'EPIC'))
@section('meta_description', $page->meta_description ?: summarise($page->intro, 155))

@section('content')
    <x-page-hero :page="$page" :breadcrumbs="['Media' => null, $page->title => null]" />

    <section class="section">
        <div class="container">
            @if ($page->intro)<p class="lead" style="max-width:76ch">{{ $page->intro }}</p>@endif

            @if ($episodes->count())
                <div class="grid grid-3 mt-4">
                    @foreach ($episodes as $episode)
                        <article class="card">
                            <a class="card-media is-wide" href="{{ route('media.podcast.show', $episode->slug) }}">
                                <img src="{{ epic_image($episode->cover_image, 'card') }}" alt="{{ $episode->title }}" loading="lazy">
                                @if ($episode->episode_number)<span class="badge badge-navy">EP {{ $episode->episode_number }}</span>@endif
                            </a>
                            <div class="card-body">
                                <h3><a href="{{ route('media.podcast.show', $episode->slug) }}">{{ $episode->title }}</a></h3>
                                @if ($episode->guest)<p class="text-muted" style="font-size:.85rem;margin:0">With {{ $episode->guest }}</p>@endif
                                <p>{{ summarise($episode->description, 120) }}</p>
                                <div class="card-meta">
                                    @if ($episode->published_at)<span>{!! icon('calendar') !!}{{ $episode->published_at->format('d M Y') }}</span>@endif
                                    @if ($episode->duration)<span>{!! icon('clock') !!}{{ $episode->duration }}</span>@endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
                {{ $episodes->links('vendor.pagination.epic') }}
            @else
                <x-empty-state title="The EPIC podcast is coming soon" icon="mic">
                    <p style="margin:0">Conversations with researchers, entrepreneurs and policymakers on the ideas shaping Pakistan's economy.</p>
                </x-empty-state>
            @endif
        </div>
    </section>

    @include('partials.sections')

    <x-subscribe-band />
@endsection
