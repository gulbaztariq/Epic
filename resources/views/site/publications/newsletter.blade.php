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
                <div class="grid grid-3 mt-4">
                    @foreach ($issues as $issue)
                        <article class="card">
                            <a class="card-media is-wide" href="{{ route('publications.show', $issue->slug) }}">
                                <img src="{{ epic_image($issue->cover_image, 'card') }}" alt="{{ $issue->title }}" loading="lazy">
                            </a>
                            <div class="card-body">
                                <h3><a href="{{ route('publications.show', $issue->slug) }}">{{ $issue->title }}</a></h3>
                                <p>{{ summarise($issue->abstract, 120) }}</p>
                                <div class="card-meta">
                                    @if ($issue->published_at)<span>{!! icon('calendar') !!}{{ $issue->published_at->format('M Y') }}</span>@endif
                                    @if ($issue->downloadUrl())
                                        <a class="section-link" href="{{ $issue->downloadUrl() }}" target="_blank" rel="noopener">Read issue {!! icon('arrow-right') !!}</a>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
                {{ $issues->links('vendor.pagination.epic') }}
            @else
                <x-empty-state title="Newsletter issues coming soon" icon="mail">
                    <p style="margin:0">Subscribe below and the EPIC e-newsletter will reach your inbox as soon as it is published.</p>
                </x-empty-state>
            @endif
        </div>
    </section>

    <section class="section section-soft">
        <div class="container container-narrow">
            <div class="form-card">
                <h2 class="section-title" style="font-size:1.5rem">Subscribe to the e-newsletter</h2>
                @include('partials.flash')
                <form action="{{ route('subscribe.store') }}" method="post">
                    @csrf
                    <input type="hidden" name="source" value="newsletter-page">
                    <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off" aria-hidden="true">
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="nl-name">Name</label>
                            <input class="form-control" id="nl-name" type="text" name="name" value="{{ old('name') }}">
                        </div>
                        <div class="form-field">
                            <label for="nl-email">Email <span class="req">*</span></label>
                            <input class="form-control" id="nl-email" type="email" name="email" value="{{ old('email') }}" required>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit">Subscribe {!! icon('arrow-right') !!}</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    @include('partials.sections')
@endsection
