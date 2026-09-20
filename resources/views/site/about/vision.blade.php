@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title).' | '.setting('site_name', 'EPIC'))
@section('meta_description', $page->meta_description ?: summarise($page->intro, 155))

@section('content')
    <x-page-hero :page="$page" :breadcrumbs="['Who We Are' => route('about.index'), $page->title => null]" />

    <section class="section">
        <div class="container">
            @if ($page->intro)
                <p class="lead text-center" style="max-width:70ch;margin-inline:auto">{{ $page->intro }}</p>
            @endif

            <div class="grid grid-2 mt-4">
                @php($visionBlock = $page->section('vision'))
                @php($missionBlock = $page->section('mission'))

                <article class="card" style="padding:34px">
                    <div class="principle-icon" style="width:60px;height:60px;margin-bottom:18px">{!! icon('eye') !!}</div>
                    <h2 style="font-size:1.75rem">{{ $visionBlock->heading ?: 'Vision' }}</h2>
                    <div class="prose" style="font-size:1.03rem">{!! rich($visionBlock->body) !!}</div>
                </article>

                <article class="card" style="padding:34px">
                    <div class="principle-icon" style="width:60px;height:60px;margin-bottom:18px;background:#e9f6e4;color:var(--green-700)">{!! icon('target') !!}</div>
                    <h2 style="font-size:1.75rem">{{ $missionBlock->heading ?: 'Mission' }}</h2>
                    <div class="prose" style="font-size:1.03rem">{!! rich($missionBlock->body) !!}</div>
                </article>
            </div>

            @if ($page->body)
                <div class="prose mt-4" style="max-width:80ch;margin-inline:auto">{!! rich($page->body) !!}</div>
            @endif
        </div>
    </section>

    @if ($page->quote)
        <section class="section section-soft">
            <div class="container container-narrow text-center">
                <blockquote style="border:0;font-size:1.55rem;padding:0;margin:0">&ldquo;{{ $page->quote }}&rdquo;</blockquote>
                @if ($page->quote_author)<p class="text-muted mt-3">{{ $page->quote_author }}</p>@endif
            </div>
        </section>
    @endif

    @include('partials.sections')

    <x-subscribe-band />
@endsection
