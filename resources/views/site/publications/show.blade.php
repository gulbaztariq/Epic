@extends('layouts.site')

@section('title', $publication->title.' | '.setting('site_name', 'EPIC'))
@section('meta_description', summarise($publication->abstract ?: $publication->body, 155))
@section('og_image', epic_image($publication->cover_image, 'portrait'))

@section('content')
    <x-page-hero
        :title="$publication->title"
        :subtitle="$publication->subtitle"
        :eyebrow="$publication->type"
        :breadcrumbs="['Publications' => route('publications.index'), $publication->title => null]" />

    <section class="section">
        <div class="container">
            <div class="content-layout">
                <div>
                    <div class="meta-row">
                        @if ($publication->authors)<span>{!! icon('people') !!}{{ $publication->authors }}</span>@endif
                        @if ($publication->published_at)<span>{!! icon('calendar') !!}{{ $publication->published_at->format('F Y') }}</span>@endif
                        @if ($publication->theme)<span>{!! icon('layers') !!}{{ $publication->theme }}</span>@endif
                    </div>

                    @if ($publication->abstract)
                        <p class="lead" style="color:var(--navy)">{{ $publication->abstract }}</p>
                    @endif

                    <div class="prose">{!! rich($publication->body) !!}</div>

                    @if ($publication->downloadUrl())
                        <a class="btn btn-green btn-lg mt-4" href="{{ $publication->downloadUrl() }}" target="_blank" rel="noopener">
                            {!! icon('download') !!} Download publication
                        </a>
                    @endif

                    <div class="share-row">
                        <span>Share</span>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(url()->current()) }}" target="_blank" rel="noopener" aria-label="Share on LinkedIn">{!! icon('linkedin') !!}</a>
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}&text={{ urlencode($publication->title) }}" target="_blank" rel="noopener" aria-label="Share on X">{!! icon('x-social') !!}</a>
                        <a href="mailto:?subject={{ rawurlencode($publication->title) }}&body={{ rawurlencode(url()->current()) }}" aria-label="Share by email">{!! icon('mail') !!}</a>
                    </div>
                </div>

                <aside class="sidebar">
                    <div class="sidebar-box" style="padding:0;overflow:hidden">
                        <img src="{{ epic_image($publication->cover_image, 'portrait') }}" alt="{{ $publication->title }}" style="width:100%">
                        <div style="padding:20px">
                            <span class="badge badge-blue">{{ $publication->type }}</span>
                            @if ($publication->downloadUrl())
                                <a class="btn btn-primary btn-block mt-3" href="{{ $publication->downloadUrl() }}" target="_blank" rel="noopener">Download</a>
                            @endif
                        </div>
                    </div>

                    @if ($related->isNotEmpty())
                        <div class="sidebar-box">
                            <h4>Related publications</h4>
                            <ul>
                                @foreach ($related as $item)
                                    <li><a href="{{ route('publications.show', $item->slug) }}">{{ $item->title }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </section>
@endsection
