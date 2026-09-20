<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', setting('site_name', 'EPIC — Economic Policy and Innovation Centre'))</title>
    <meta name="description" content="@yield('meta_description', setting('site_description', 'EPIC is an independent policy, research and knowledge institution advancing evidence-based solutions for economic prosperity, human capital, governance, entrepreneurship and responsible innovation.'))">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ setting('site_name', 'EPIC') }}">
    <meta property="og:title" content="@yield('title', setting('site_name', 'EPIC'))">
    <meta property="og:description" content="@yield('meta_description', setting('site_description', ''))">
    <meta property="og:image" content="@yield('og_image', site_logo())">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" type="image/png" href="{{ setting('favicon') ? uploaded_url(setting('favicon')) : asset('images/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ setting('favicon') ? uploaded_url(setting('favicon')) : asset('images/favicon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,500;1,600&display=swap" rel="stylesheet">

    <script>document.documentElement.classList.add('js');</script>
    <link rel="stylesheet" href="{{ asset_v('css/site.css') }}">

    @if (setting('head_code'))
        {!! setting('head_code') !!}
    @endif
    @stack('head')
</head>
<body class="@yield('body_class')">
    <a class="skip-link" href="#main">Skip to content</a>

    @include('partials.header')

    <main id="main">
        @yield('content')
    </main>

    @include('partials.footer')

    @include('partials.mobile-nav')
    @include('partials.search-panel')

    <button class="back-to-top" type="button" aria-label="Back to top">{!! icon('arrow-right') !!}</button>

    <script src="{{ asset_v('js/site.js') }}" defer></script>
    @if (setting('body_code'))
        {!! setting('body_code') !!}
    @endif
    @stack('scripts')
</body>
</html>
