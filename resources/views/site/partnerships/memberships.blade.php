@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title).' | '.setting('site_name', 'EPIC'))
@section('meta_description', $page->meta_description ?: summarise($page->intro, 155))

@section('content')
    <x-page-hero :page="$page" :breadcrumbs="['Partnerships & MoUs' => route('partnerships.index'), $page->title => null]" />

    <section class="section">
        <div class="container">
            @if ($page->intro)
                <p class="lead" style="max-width:76ch">{{ $page->intro }}</p>
            @endif

            @if ($networks->isNotEmpty())
                <h2 class="section-title mt-4" style="font-size:1.45rem">Networks we engage with</h2>
                <x-check-list :items="$networks" :columns="3" />
            @endif

            @if ($partners->isNotEmpty())
                <h2 class="section-title" style="font-size:1.45rem;margin-top:52px">Our memberships</h2>
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
                                {!! icon('network', 'icon', '1.5') !!}
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

    @include('partials.sections')

    <x-subscribe-band />
@endsection
