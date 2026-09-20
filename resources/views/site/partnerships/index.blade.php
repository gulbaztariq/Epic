@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title).' | '.setting('site_name', 'EPIC'))
@section('meta_description', $page->meta_description ?: summarise($page->intro, 155))

@section('content')
    <x-page-hero :page="$page" :breadcrumbs="['Partnerships & MoUs' => null, $page->title => null]" />

    <section class="section">
        <div class="container">
            @if ($page->intro)
                <p class="lead" style="max-width:76ch">{{ $page->intro }}</p>
            @endif

            @if ($categories->isNotEmpty())
                <h2 class="section-title mt-4" style="font-size:1.45rem">We work with</h2>
                <x-check-list :items="$categories" :columns="3" />
            @endif

            @if ($partners->isNotEmpty())
                <h2 class="section-title" style="font-size:1.45rem;margin-top:52px">Our partners</h2>
                <div class="logo-grid">
                    @foreach ($partners as $partner)
                        @if ($partner->website)
                            <a class="logo-card" href="{{ $partner->website }}" target="_blank" rel="noopener">
                        @else
                            <div class="logo-card">
                        @endif
                            @if ($partner->logo)
                                <img src="{{ uploaded_url($partner->logo) }}" alt="{{ $partner->name }}" loading="lazy">
                            @else
                                {!! icon('partnership', 'icon', '1.5') !!}
                            @endif
                            <span class="logo-name">{{ $partner->name }}</span>
                            @if ($partner->category)<span class="logo-cat">{{ $partner->category }}</span>@endif
                        @if ($partner->website)</a>@else</div>@endif
                    @endforeach
                </div>
            @endif

            @if ($page->body)
                <div class="prose mt-4">{!! rich($page->body) !!}</div>
            @endif
        </div>
    </section>

    <section class="section section-soft">
        <div class="container">
            <div class="grid grid-2">
                <a class="card" href="{{ route('partnerships.mous') }}" style="padding:28px">
                    <div class="principle-icon" style="margin-bottom:14px">{!! icon('document') !!}</div>
                    <h3>Memoranda of Understanding</h3>
                    <p style="font-size:.93rem">Strategic MoUs for joint research, student engagement, co-publication and capacity building.</p>
                    <span class="section-link mt-3">View MoUs {!! icon('arrow-right') !!}</span>
                </a>
                <a class="card" href="{{ route('partnerships.memberships') }}" style="padding:28px">
                    <div class="principle-icon" style="margin-bottom:14px;background:#e9f6e4;color:var(--green-700)">{!! icon('network') !!}</div>
                    <h3>Memberships</h3>
                    <p style="font-size:.93rem">The national and international networks EPIC participates in.</p>
                    <span class="section-link mt-3">View memberships {!! icon('arrow-right') !!}</span>
                </a>
            </div>
        </div>
    </section>

    @include('partials.sections')

    <x-cta-band title="Propose a partnership"
        text="EPIC believes sustainable impact is built through collaboration. Tell us what you would like to build together."
        primary-label="Contact our partnerships team" />
@endsection
