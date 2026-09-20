@extends('layouts.site')

@section('title', $episode->title.' | Podcast | '.setting('site_name', 'EPIC'))
@section('meta_description', summarise($episode->description, 155))

@section('content')
    <x-page-hero
        :title="$episode->title"
        :eyebrow="$episode->episode_number ? 'Episode '.$episode->episode_number : 'EPIC Podcast'"
        :breadcrumbs="['Media' => null, 'Podcast' => route('media.podcast'), $episode->title => null]" />

    <section class="section">
        <div class="container">
            <div class="content-layout">
                <div>
                    @if ($episode->embed_url)
                        <div class="video-embed" style="aspect-ratio:auto;min-height:180px;background:var(--bg-soft)">
                            <iframe src="{{ $episode->embed_url }}" title="{{ $episode->title }}" loading="lazy" allow="autoplay; clipboard-write; encrypted-media"></iframe>
                        </div>
                    @elseif ($episode->audio_url)
                        <audio controls style="width:100%" src="{{ uploaded_url($episode->audio_url) }}">Your browser does not support audio playback.</audio>
                    @else
                        <img src="{{ epic_image($episode->cover_image, 'wide') }}" alt="{{ $episode->title }}" style="border-radius:var(--radius-lg);width:100%">
                    @endif

                    <div class="meta-row mt-4">
                        @if ($episode->guest)<span>{!! icon('people') !!}{{ $episode->guest }}</span>@endif
                        @if ($episode->published_at)<span>{!! icon('calendar') !!}{{ $episode->published_at->format('d F Y') }}</span>@endif
                        @if ($episode->duration)<span>{!! icon('clock') !!}{{ $episode->duration }}</span>@endif
                    </div>

                    <div class="prose">{!! rich($episode->description) !!}</div>
                </div>

                <aside class="sidebar">
                    @if ($related->isNotEmpty())
                        <div class="sidebar-box">
                            <h4>More episodes</h4>
                            <ul>
                                @foreach ($related as $item)
                                    <li><a href="{{ route('media.podcast.show', $item->slug) }}">{{ $item->title }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </section>
@endsection
