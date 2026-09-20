@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title).' | '.setting('site_name', 'EPIC'))
@section('meta_description', $page->meta_description ?: summarise($page->intro ?: $page->body, 155))

@section('content')
    <x-page-hero :page="$page" :breadcrumbs="['Who We Are' => null, $page->title => null]" />

    <section class="section">
        <div class="container">
            <div class="split">
                <div>
                    @if ($page->intro)
                        <p class="lead" style="color:var(--navy);font-family:var(--font-display);font-size:1.28rem;line-height:1.55">{{ $page->intro }}</p>
                    @endif
                    <div class="prose">{!! rich($page->body) !!}</div>
                </div>
                <div class="split-media">
                    <img src="{{ $page->hero_image ? uploaded_url($page->hero_image) : asset('images/hero-islamabad.svg') }}"
                         alt="{{ $page->title }}" loading="lazy">
                    @if ($page->quote)
                        <blockquote class="mt-4">{{ $page->quote }}
                            @if ($page->quote_author)<cite style="display:block;font-size:.85rem;font-style:normal;color:var(--muted);margin-top:8px">{{ $page->quote_author }}</cite>@endif
                        </blockquote>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="section section-soft">
        <div class="container">
            <div class="grid grid-3">
                <a class="card" href="{{ route('about.vision') }}" style="padding:26px">
                    <div class="principle-icon" style="margin-bottom:14px">{!! icon('target') !!}</div>
                    <h3>Vision &amp; Mission</h3>
                    <p style="font-size:.92rem">What EPIC is working towards, and how we get there.</p>
                    <span class="section-link mt-3">Read more {!! icon('arrow-right') !!}</span>
                </a>
                <a class="card" href="{{ route('about.principles') }}" style="padding:26px">
                    <div class="principle-icon" style="margin-bottom:14px">{!! icon('shield') !!}</div>
                    <h3>EPIC Principles</h3>
                    <p style="font-size:.92rem">The values that guide our research, partnerships and policy engagement.</p>
                    <span class="section-link mt-3">Read more {!! icon('arrow-right') !!}</span>
                </a>
                <a class="card" href="{{ route('about.strengths') }}" style="padding:26px">
                    <div class="principle-icon" style="margin-bottom:14px">{!! icon('star') !!}</div>
                    <h3>Our Strengths</h3>
                    <p style="font-size:.92rem">The multidisciplinary capabilities we bring to every engagement.</p>
                    <span class="section-link mt-3">Read more {!! icon('arrow-right') !!}</span>
                </a>
            </div>
        </div>
    </section>

    @include('partials.sections')

    <x-cta-band :page="$page"
        title="Partner with EPIC"
        text="We collaborate with universities, think tanks, government institutions, development partners and the private sector."
        primary-label="Get in touch"
        secondary-label="Our partnerships"
        :secondary-url="route('partnerships.index')" />
@endsection
