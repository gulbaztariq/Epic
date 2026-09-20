@extends('layouts.site')

@section('title', $project->title.' | '.setting('site_name', 'EPIC'))
@section('meta_description', summarise($project->summary ?: $project->description, 155))
@section('og_image', epic_image($project->image, 'wide'))

@section('content')
    <x-page-hero
        :title="$project->title"
        :subtitle="$project->summary"
        :eyebrow="$project->category"
        :breadcrumbs="['What We Do' => route('work.themes'), 'Projects' => route('work.projects'), $project->title => null]" />

    <section class="section">
        <div class="container">
            <div class="content-layout">
                <div>
                    <img src="{{ epic_image($project->image, 'wide') }}" alt="{{ $project->title }}"
                         style="border-radius:var(--radius-lg);width:100%;margin-bottom:28px">

                    <div class="prose">{!! rich($project->description) !!}</div>
                </div>

                <aside class="sidebar">
                    <div class="sidebar-box">
                        <h4>Project details</h4>
                        <ul class="contact-lines" style="gap:14px">
                            <li>{!! icon('flag') !!}<div><strong>Status</strong><span>{{ \App\Models\Project::STATUSES[$project->status] ?? $project->status }}</span></div></li>
                            @if ($project->category)
                                <li>{!! icon('layers') !!}<div><strong>Theme</strong><span>{{ $project->category }}</span></div></li>
                            @endif
                            @if ($project->started_at)
                                <li>{!! icon('calendar') !!}<div><strong>Timeline</strong><span>{{ $project->started_at->format('M Y') }}@if ($project->ended_at) &ndash; {{ $project->ended_at->format('M Y') }}@endif</span></div></li>
                            @endif
                            @if ($project->partners)
                                <li>{!! icon('partnership') !!}<div><strong>Partners</strong><span>{{ $project->partners }}</span></div></li>
                            @endif
                        </ul>
                        <a class="btn btn-primary btn-block mt-3" href="{{ route('contact') }}">Enquire about this project</a>
                    </div>

                    @if ($related->isNotEmpty())
                        <div class="sidebar-box">
                            <h4>Other projects</h4>
                            <ul>
                                @foreach ($related as $item)
                                    <li><a href="{{ route('work.projects.show', $item->slug) }}">{{ $item->title }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </section>
@endsection
