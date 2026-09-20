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

            @if ($principles->isNotEmpty())
                <div class="principle-list mt-4">
                    @foreach ($principles as $principle)
                        <article class="principle">
                            <div class="principle-icon">{!! icon($principle->icon ?: 'check') !!}</div>
                            <div>
                                <h3>{{ $principle->title }}</h3>
                                <p>{{ $principle->description }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <x-empty-state title="Principles coming soon">Our guiding principles will be published here shortly.</x-empty-state>
            @endif

            @if ($page->body)
                <div class="prose mt-4">{!! rich($page->body) !!}</div>
            @endif
        </div>
    </section>

    @include('partials.sections')

    <x-cta-band title="See these principles in practice" text="Explore the research, projects and dialogues that put EPIC's principles to work."
        primary-label="Our publications" :primary-url="route('publications.index')"
        secondary-label="What we do" :secondary-url="route('work.themes')" />
@endsection
