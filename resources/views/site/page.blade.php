@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title).' | '.setting('site_name', 'EPIC'))
@section('meta_description', $page->meta_description ?: summarise($page->intro ?: $page->body, 155))

@section('content')
    <x-page-hero :page="$page" :breadcrumbs="[$page->title => null]" />

    @if ($page->intro || $page->body)
        <section class="section">
            <div class="container container-narrow">
                @if ($page->intro)<p class="lead">{{ $page->intro }}</p>@endif
                <div class="prose">{!! rich($page->body) !!}</div>
            </div>
        </section>
    @endif

    @include('partials.sections')
@endsection
