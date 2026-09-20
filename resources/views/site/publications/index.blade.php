@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title).' | '.setting('site_name', 'EPIC'))
@section('meta_description', $page->meta_description ?: summarise($page->intro, 155))

@section('content')
    <x-page-hero :page="$page" :breadcrumbs="['Publications' => null, $page->title => null]" />

    <section class="section">
        <div class="container">
            @if ($page->intro)
                <p class="lead" style="max-width:76ch">{{ $page->intro }}</p>
            @endif

            @if ($types->isNotEmpty())
                <div class="chip-row mt-4" style="margin-bottom:30px">
                    <a class="chip {{ ! $activeType ? 'is-active' : '' }}" href="{{ route('publications.index') }}">All</a>
                    @foreach ($types as $type)
                        <a class="chip {{ $activeType === $type ? 'is-active' : '' }}" href="{{ route('publications.index', ['type' => $type]) }}">{{ $type }}</a>
                    @endforeach
                </div>
            @endif

            @if ($publications->count())
                <div class="grid grid-auto-sm">
                    @foreach ($publications as $publication)
                        <x-pub-card :publication="$publication" />
                    @endforeach
                </div>
                {{ $publications->links('vendor.pagination.epic') }}
            @else
                <x-empty-state title="Publications coming soon" icon="document">
                    <p style="margin:0">EPIC research reports, policy briefs and working papers will appear here.</p>
                </x-empty-state>
            @endif

            @if ($collectionTypes->isNotEmpty())
                <div style="margin-top:56px">
                    <h2 class="section-title" style="font-size:1.45rem">Our collection includes</h2>
                    <x-check-list :items="$collectionTypes" :columns="3" />
                </div>
            @endif
        </div>
    </section>

    @include('partials.sections')

    <x-subscribe-band />
@endsection
