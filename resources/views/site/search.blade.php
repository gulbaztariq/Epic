@extends('layouts.site')

@section('title', 'Search | '.setting('site_name', 'EPIC'))

@section('content')
    <x-page-hero title="Search" :subtitle="$term ? 'Results for “'.$term.'”' : 'Search EPIC publications, events, projects and news.'" :breadcrumbs="['Search' => null]" />

    <section class="section">
        <div class="container container-narrow">
            <form action="{{ route('search') }}" method="get" class="subscribe-inline" style="margin-bottom:34px">
                <input class="form-control" type="search" name="q" value="{{ $term }}" placeholder="Search…" aria-label="Search" style="flex:1 1 240px">
                <button class="btn btn-primary" type="submit">{!! icon('search') !!} Search</button>
            </form>

            @if ($term === '')
                <p class="text-muted">Enter a search term above to begin.</p>
            @elseif ($results->isEmpty())
                <x-empty-state title="No results found" icon="search">
                    <p style="margin:0">We could not find anything for &ldquo;{{ $term }}&rdquo;. Try a different keyword, or
                        <a href="{{ route('publications.index') }}">browse our publications</a>.</p>
                </x-empty-state>
            @else
                <p class="text-muted">{{ $results->count() }} result{{ $results->count() === 1 ? '' : 's' }} found.</p>
                <div class="grid" style="gap:14px;margin-top:20px">
                    @foreach ($results as $result)
                        <article class="card" style="padding:20px">
                            <div class="card-meta" style="margin:0 0 8px">
                                <span class="badge badge-blue">{{ $result['type'] }}</span>
                                @if ($result['date'])<span>{!! icon('calendar') !!}{{ $result['date']->format('M Y') }}</span>@endif
                            </div>
                            <h3 style="font-size:1.12rem;margin-bottom:6px"><a href="{{ $result['url'] }}">{{ $result['title'] }}</a></h3>
                            <p style="font-size:.92rem;margin:0">{{ $result['summary'] }}</p>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
