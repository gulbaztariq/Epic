@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title).' | '.setting('site_name', 'EPIC'))
@section('meta_description', $page->meta_description ?: summarise($page->intro, 155))

@section('content')
    <x-page-hero :page="$page" :breadcrumbs="['What We Do' => route('work.themes'), $page->title => null]" />

    <section class="section">
        <div class="container">
            @if ($page->intro)
                <p class="lead" style="max-width:76ch">{{ $page->intro }}</p>
            @endif

            @if ($projects->count())
                <div class="grid grid-3 mt-4">
                    @foreach ($projects as $project)
                        <x-project-card :project="$project" />
                    @endforeach
                </div>
                {{ $projects->links('vendor.pagination.epic') }}
            @else
                <x-empty-state title="Projects will be published here" icon="briefcase">
                    <p style="margin:0">EPIC designs and implements research, policy, capacity-building and development projects
                        independently and with partner organisations.</p>
                </x-empty-state>
            @endif

            @if ($projectTypes->isNotEmpty())
                <div style="margin-top:54px">
                    <h2 class="section-title" style="font-size:1.45rem">Our projects may include</h2>
                    <x-check-list :items="$projectTypes" :columns="3" />
                </div>
            @endif
        </div>
    </section>

    @include('partials.sections')

    <x-cta-band :page="$page" title="Commission a project with EPIC"
        text="From policy research and labour-market assessments to entrepreneurship programmes and impact evaluations."
        primary-label="Start a conversation" />
@endsection
