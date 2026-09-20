@props([
    'page' => null,
    'title' => null,
    'subtitle' => null,
    'eyebrow' => null,
    'breadcrumbs' => [],
    'image' => null,
])

@php
    $heroTitle = $title ?: ($page?->hero_title ?: $page?->title);
    $heroSubtitle = $subtitle ?: $page?->hero_subtitle;
    $heroEyebrow = $eyebrow ?: $page?->eyebrow;
    $heroImage = $image ?: $page?->hero_image;
@endphp

<section class="page-hero {{ $heroImage ? 'has-image' : '' }}"
    @if ($heroImage) style="background-image:url('{{ uploaded_url($heroImage) }}')" @endif>
    <div class="container">
        @if (count($breadcrumbs))
            <ul class="breadcrumbs">
                <li><a href="{{ route('home') }}">Home</a></li>
                @foreach ($breadcrumbs as $label => $url)
                    <li>@if ($url)<a href="{{ $url }}">{{ $label }}</a>@else <span>{{ $label }}</span> @endif</li>
                @endforeach
            </ul>
        @endif

        @if ($heroEyebrow)
            <p class="eyebrow eyebrow-light">{{ $heroEyebrow }}</p>
        @endif

        <h1>{{ $heroTitle }}</h1>

        @if ($heroSubtitle)
            <p>{{ $heroSubtitle }}</p>
        @endif

        {{ $slot }}
    </div>
</section>
