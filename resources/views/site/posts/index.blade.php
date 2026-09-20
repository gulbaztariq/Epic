@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title).' | '.setting('site_name', 'EPIC'))
@section('meta_description', $page->meta_description ?: summarise($page->intro, 155))

@section('content')
    <x-page-hero :page="$page" :breadcrumbs="['Publications' => route('publications.index'), $page->title => null]" />

    <section class="section">
        <div class="container">
            @if ($page->intro)
                <p class="lead" style="max-width:76ch">{{ $page->intro }}</p>
            @endif

            <div class="chip-row mt-4" style="margin-bottom:28px">
                <a class="chip {{ ! $activeCategory ? 'is-active' : '' }}" href="{{ route('blogs.index') }}">All</a>
                <a class="chip {{ $activeCategory === 'blog' ? 'is-active' : '' }}" href="{{ route('blogs.index', ['category' => 'blog']) }}">Blogs</a>
                <a class="chip {{ $activeCategory === 'article' ? 'is-active' : '' }}" href="{{ route('blogs.index', ['category' => 'article']) }}">Articles</a>
            </div>

            @if ($posts->count())
                <div class="grid grid-3">
                    @foreach ($posts as $post)
                        <x-post-card :post="$post" />
                    @endforeach
                </div>
                {{ $posts->links('vendor.pagination.epic') }}
            @else
                <x-empty-state title="Blogs and articles coming soon" icon="edit">
                    <p style="margin:0">Commentary and analysis from the EPIC team and our network of experts.</p>
                </x-empty-state>
            @endif
        </div>
    </section>

    @include('partials.sections')

    <x-subscribe-band />
@endsection
