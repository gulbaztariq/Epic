@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title).' | '.setting('site_name', 'EPIC'))
@section('meta_description', $page->meta_description ?: summarise($page->intro, 155))

@section('content')
    <x-page-hero :page="$page" :breadcrumbs="['What We Do' => null, $page->title => null]" />

    <section class="section">
        <div class="container">
            @if ($page->intro)
                <p class="lead" style="max-width:76ch">{{ $page->intro }}</p>
            @endif

            @if ($themes->isNotEmpty())
                <div class="grid grid-2 mt-4">
                    @foreach ($themes as $theme)
                        <article class="principle">
                            <div class="principle-icon">{!! icon($theme->icon ?: 'layers') !!}</div>
                            <div>
                                <h3>{{ $theme->title }}</h3>
                                <p>{{ $theme->description }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <x-empty-state title="Thematic areas coming soon" />
            @endif

            @if ($page->body)
                <div class="prose mt-4">{!! rich($page->body) !!}</div>
            @endif
        </div>
    </section>

    <section class="section section-soft">
        <div class="container">
            <div class="grid grid-3">
                <a class="card" href="{{ route('work.projects') }}" style="padding:26px">
                    <div class="principle-icon" style="margin-bottom:14px">{!! icon('briefcase') !!}</div>
                    <h3>Projects</h3>
                    <p style="font-size:.92rem">Research, policy, capacity-building and development projects.</p>
                    <span class="section-link mt-3">View projects {!! icon('arrow-right') !!}</span>
                </a>
                <a class="card" href="{{ route('work.chapters') }}" style="padding:26px">
                    <div class="principle-icon" style="margin-bottom:14px">{!! icon('globe') !!}</div>
                    <h3>International Chapters</h3>
                    <p style="font-size:.92rem">Our growing global network of chapters and collaborators.</p>
                    <span class="section-link mt-3">Explore chapters {!! icon('arrow-right') !!}</span>
                </a>
                <a class="card" href="{{ route('publications.index') }}" style="padding:26px">
                    <div class="principle-icon" style="margin-bottom:14px">{!! icon('document') !!}</div>
                    <h3>Publications</h3>
                    <p style="font-size:.92rem">Reports, policy briefs, working papers and journal articles.</p>
                    <span class="section-link mt-3">Read our research {!! icon('arrow-right') !!}</span>
                </a>
            </div>
        </div>
    </section>

    @include('partials.sections')

    <x-subscribe-band />
@endsection
