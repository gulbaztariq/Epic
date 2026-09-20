@extends('layouts.site')

@section('title', 'Access denied | '.setting('site_name', 'EPIC'))

@section('content')
    <section class="section" style="padding-block:clamp(60px,9vw,120px)">
        <div class="container container-narrow text-center">
            <p class="eyebrow" style="justify-content:center">Error 403</p>
            <h1>This page is not available</h1>
            <p class="lead">You do not have permission to view this page.</p>
            <div class="hero-actions" style="justify-content:center">
                <a class="btn btn-primary btn-lg" href="{{ route('home') }}">Back to home</a>
                <a class="btn btn-outline btn-lg" href="{{ route('publications.index') }}">Browse publications</a>
            </div>
        </div>
    </section>
@endsection
