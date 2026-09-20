@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title).' | '.setting('site_name', 'EPIC'))
@section('meta_description', $page->meta_description ?: summarise($page->intro, 155))

@section('content')
    <x-page-hero :page="$page" :breadcrumbs="['Publications' => route('publications.index'), $page->title => null]" />

    <section class="section">
        <div class="container">
            @if ($page->intro)
                <p class="lead" style="max-width:76ch">{{ $page->intro }}</p>
            @endif

            @if ($issues->count())
                <div class="grid mt-4" style="gap:20px">
                    @foreach ($issues as $issue)
                        <article class="list-card">
                            <div class="list-media">
                                <img src="{{ epic_image($issue->cover_image, 'portrait') }}" alt="{{ $issue->title }}" loading="lazy">
                            </div>
                            <div class="list-body">
                                <div class="card-meta" style="margin:0">
                                    <span class="badge badge-blue">{{ $issue->type }}</span>
                                    @if ($issue->issue)<span>{!! icon('book') !!}{{ $issue->issue }}</span>@endif
                                    @if ($issue->published_at)<span>{!! icon('calendar') !!}{{ $issue->published_at->format('M Y') }}</span>@endif
                                </div>
                                <h3><a href="{{ route('publications.show', $issue->slug) }}">{{ $issue->title }}</a></h3>
                                @if ($issue->authors)<p class="text-muted" style="font-size:.88rem;margin:0">{{ $issue->authors }}</p>@endif
                                <p style="font-size:.94rem">{{ summarise($issue->abstract ?: $issue->body, 220) }}</p>
                                <div class="list-actions">
                                    <a class="btn btn-outline btn-sm" href="{{ route('publications.show', $issue->slug) }}">Read more</a>
                                    @if ($issue->downloadUrl())
                                        <a class="btn btn-green btn-sm" href="{{ $issue->downloadUrl() }}" target="_blank" rel="noopener">{!! icon('download') !!} Download</a>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
                {{ $issues->links('vendor.pagination.epic') }}
            @else
                <x-empty-state title="Journal issues coming soon" icon="book">
                    <p style="margin:0">EPIC's HEC-recognized journal will publish peer-reviewed research on economic policy,
                        human capital, governance, entrepreneurship and responsible innovation.</p>
                </x-empty-state>
            @endif

            @if ($page->body)
                <div class="prose mt-4" style="margin-top:48px">{!! rich($page->body) !!}</div>
            @endif
        </div>
    </section>

    @include('partials.sections')

    <x-cta-band :page="$page" title="Submit to the EPIC journal"
        text="We welcome original research from academics, practitioners and policy professionals."
        primary-label="Submission enquiries" />
@endsection
