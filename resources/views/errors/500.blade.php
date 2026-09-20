@extends('layouts.site')

@section('title', 'Something went wrong | '.setting('site_name', 'EPIC'))

@section('content')
    <section class="section" style="padding-block:clamp(60px,9vw,120px)">
        <div class="container container-narrow text-center">
            <p class="eyebrow" style="justify-content:center">Error 500</p>
            <h1>Something went wrong</h1>
            <p class="lead">Our team has been notified. Please try again in a few moments.</p>
            <a class="btn btn-primary btn-lg" href="{{ route('home') }}">Back to home</a>
        </div>
    </section>
@endsection
