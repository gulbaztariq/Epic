@extends('layouts.site')

@section('title', $page->meta_title ?: setting('site_name', 'EPIC — Economic Policy and Innovation Centre'))
@section('meta_description', $page->meta_description ?: setting('site_description'))

@section('content')
    @php
        $heroLines = preg_split('/\r\n|\r|\n/', trim((string) ($page->hero_title ?: 'Evidence. Innovation. Opportunity.')));
        $heroLines = array_values(array_filter(array_map('trim', $heroLines), 'strlen'));
        $lastLine = count($heroLines) - 1;
    @endphp

    {{-- ---------------------------------------------------------------- Hero --}}
    <section class="hero">
        <div class="container">
            <div class="hero-grid">
                <div class="hero-copy">
                    @if ($page->eyebrow)
                        <p class="eyebrow">{{ $page->eyebrow }}</p>
                    @endif

                    <h1 class="hero-title">
                        @foreach ($heroLines as $i => $line)
                            <span class="{{ $i === $lastLine && count($heroLines) > 1 ? 'accent' : '' }}">{{ $line }}</span>
                        @endforeach
                    </h1>

                    @if ($page->hero_subtitle)
                        <p class="hero-lead">{{ $page->hero_subtitle }}</p>
                    @endif

                    <div class="hero-actions">
                        <a class="btn btn-primary btn-lg" href="{{ $page->cta_url ?: route('publications.index') }}">
                            {{ $page->cta_text ?: 'Explore Our Research' }} {!! icon('arrow-right') !!}
                        </a>
                        <a class="btn btn-outline btn-lg" href="{{ setting('home_cta2_url', route('about.index')) }}">
                            {{ setting('home_cta2_label', 'About EPIC') }}
                        </a>
                    </div>
                </div>

                <div class="hero-media">
                    <div class="hero-media-frame">
                        <img src="{{ $page->hero_image ? uploaded_url($page->hero_image) : asset('images/hero-islamabad.svg') }}"
                             alt="{{ setting('site_name', 'EPIC') }}" width="1200" height="750">
                    </div>

                    @if ($page->quote)
                        <figure class="hero-quote">
                            <p>&ldquo;{{ $page->quote }}&rdquo;</p>
                            @if ($page->quote_author)
                                <cite>{{ $page->quote_author }}</cite>
                            @endif
                        </figure>
                    @endif
                </div>
            </div>

            @if ($highlights->isNotEmpty())
                <div class="hero-highlights">
                    @foreach ($highlights as $highlight)
                        <div class="hero-highlight">
                            {!! icon($highlight->icon ?: 'chart') !!}
                            <span>{{ $highlight->title }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- -------------------------------------------------------- Focus areas --}}
    @if ($focusAreas->isNotEmpty())
        @php($focusSection = $page->section('focus'))
        <section class="section focus-strip">
            <div class="container">
                <div class="section-head">
                    <h2 class="section-title">{{ $focusSection->heading ?: 'Our Focus Areas' }}</h2>
                    <a class="section-link" href="{{ $focusSection->link_url ?: route('work.themes') }}">
                        {{ $focusSection->link_text ?: 'A more innovative, competitive and inclusive Pakistan' }} {!! icon('arrow-right') !!}
                    </a>
                </div>

                <div class="focus-grid reveal">
                    @foreach ($focusAreas as $area)
                        <a class="focus-item {{ $area->color === 'green' ? 'is-green' : '' }}" href="{{ $area->url ?: route('work.themes') }}">
                            {!! icon($area->icon) !!}
                            <h3>{{ $area->title }}</h3>
                            <p>{{ $area->description }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ---------------------------------------------- Featured publications --}}
    @if ($publications->isNotEmpty())
        @php($pubSection = $page->section('publications'))
        <section class="section">
            <div class="container">
                <div class="section-head">
                    <h2 class="section-title">{{ $pubSection->heading ?: 'Featured Publications' }}</h2>
                    <a class="section-link" href="{{ $pubSection->link_url ?: route('publications.index') }}">
                        {{ $pubSection->link_text ?: 'View All Publications' }} {!! icon('arrow-right') !!}
                    </a>
                </div>

                <div class="grid grid-auto-sm reveal">
                    @foreach ($publications as $publication)
                        <x-pub-card :publication="$publication" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ------------------------------------------------------------- Events --}}
    @if ($events->isNotEmpty())
        @php($eventSection = $page->section('events'))
        <section class="section section-soft">
            <div class="container">
                <div class="section-head">
                    <h2 class="section-title">{{ $eventSection->heading ?: 'Upcoming Events & Dialogues' }}</h2>
                    <a class="section-link" href="{{ $eventSection->link_url ?: route('events.index') }}">
                        {{ $eventSection->link_text ?: 'View All Events' }} {!! icon('arrow-right') !!}
                    </a>
                </div>

                <div class="grid grid-3 reveal">
                    @foreach ($events as $event)
                        <x-event-card :event="$event" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- --------------------------------------------------- Entrepreneurship --}}
    @php($band = $page->section('entrepreneurship'))
    @if ($band->heading || $pillars->isNotEmpty())
        <section class="section feature-band">
            <div class="container">
                <div class="feature-grid">
                    <div>
                        <h2 class="section-title" style="display:block">{!! nl2br(e($band->heading ?: 'Entrepreneurship for a Brighter Pakistan')) !!}</h2>
                        <span style="display:block;width:46px;height:3px;background:var(--green);border-radius:2px;margin:14px 0 18px"></span>
                        <p>{{ $band->body ?: 'We support evidence-based policies, partnerships and programmes that enable entrepreneurs, scale innovation, develop human capital and create quality jobs across Pakistan.' }}</p>
                        <a class="btn btn-primary mt-3" href="{{ $band->link_url ?: route('work.themes') }}">
                            {{ $band->link_text ?: 'Our Entrepreneurship Agenda' }} {!! icon('arrow-right') !!}
                        </a>
                    </div>

                    @if ($pillars->isNotEmpty())
                        <div class="pillars">
                            @foreach ($pillars as $pillar)
                                <div class="pillar">
                                    {!! icon($pillar->icon ?: 'rocket') !!}
                                    <h4>{{ $pillar->title }}</h4>
                                    <p>{{ $pillar->description }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($band->subheading)
                        <blockquote class="pull-quote" style="margin:0">{{ $band->subheading }}</blockquote>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- ---------------------------------------------------- Data & insights --}}
    @if ($stats->isNotEmpty())
        @php($statSection = $page->section('stats'))
        <section class="section">
            <div class="container">
                <div class="section-head">
                    <h2 class="section-title">{{ $statSection->heading ?: 'Data & Insights' }}</h2>
                    <a class="section-link" href="{{ $statSection->link_url ?: route('publications.index') }}">
                        {{ $statSection->link_text ?: 'Explore More Insights' }} {!! icon('arrow-right') !!}
                    </a>
                </div>

                <div class="stats-grid reveal">
                    @foreach ($stats as $stat)
                        <div class="stat-card">
                            <span class="stat-label">{{ $stat->label }}</span>
                            <div class="stat-row">
                                <span class="stat-value">{{ $stat->value }}</span>
                                {!! icon($stat->icon) !!}
                            </div>
                            <span class="stat-caption">{{ $stat->caption }}</span>
                        </div>
                    @endforeach

                    @if ($statSection->body)
                        <p class="stats-note">{{ $statSection->body }}</p>
                    @endif
                </div>
            </div>
        </section>
    @endif

    <x-subscribe-band />
@endsection
