@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title).' | '.setting('site_name', 'EPIC'))
@section('meta_description', $page->meta_description ?: summarise($page->intro, 155))

@section('content')
    <x-page-hero :page="$page" :breadcrumbs="['Who We Are' => route('about.index'), $page->title => null]" />

    <section class="section">
        <div class="container">
            @if ($page->intro)
                <p class="lead" style="max-width:74ch">{{ $page->intro }}</p>
            @endif

            <h2 class="section-title mt-4" style="font-size:1.5rem">Our key institutional strengths</h2>

            @if ($strengths->isNotEmpty())
                <x-check-list :items="$strengths" :columns="2" />
            @else
                <x-empty-state title="Content coming soon">Institutional strengths will be listed here.</x-empty-state>
            @endif

            @if ($page->body)
                <div class="prose mt-4">{!! rich($page->body) !!}</div>
            @endif
        </div>
    </section>

    @include('partials.sections')

    <x-cta-band :page="$page" title="Looking for a research or delivery partner?"
        text="EPIC combines research, policy engagement, academic expertise, entrepreneurship and implementation experience within one multidisciplinary platform."
        primary-label="Talk to our team" />
@endsection
