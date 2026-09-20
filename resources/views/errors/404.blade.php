@extends('layouts.site')

@section('title', 'Page not found | '.setting('site_name', 'EPIC'))

@section('content')
    <section class="section" style="padding-block:clamp(60px,9vw,120px)">
        <div class="container container-narrow text-center">
            <p class="eyebrow" style="justify-content:center">Error 404</p>
            <h1>We couldn't find that page</h1>
            <p class="lead">The page you are looking for may have moved, or the link may be out of date.</p>
            <div class="hero-actions" style="justify-content:center">
                <a class="btn btn-primary btn-lg" href="{{ route('home') }}">Back to home</a>
                <a class="btn btn-outline btn-lg" href="{{ route('publications.index') }}">Browse publications</a>
            </div>
        </div>
    </section>
@endsection
