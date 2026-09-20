@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title).' | '.setting('site_name', 'EPIC'))
@section('meta_description', $page->meta_description ?: summarise($page->intro, 155))

@section('content')
    <x-page-hero :page="$page" :breadcrumbs="['Media' => null, $page->title => null]" />

    <section class="section">
        <div class="container">
            @if ($page->intro)<p class="lead" style="max-width:76ch">{{ $page->intro }}</p>@endif

            @if ($posts->count())
                <div class="grid mt-4" style="gap:18px">
                    @foreach ($posts as $post)
                        <article class="list-card is-wide">
                            <div class="list-media">
                                <img src="{{ epic_image($post->image, 'card') }}" alt="{{ $post->title }}" loading="lazy">
                            </div>
                            <div class="list-body">
                                <div class="card-meta" style="margin:0">
                                    <span class="badge badge-navy">Press Release</span>
                                    @if ($post->published_at)<span>{!! icon('calendar') !!}{{ $post->published_at->format('d M Y') }}</span>@endif
                                </div>
                                <h3><a href="{{ route('media.press.show', $post->slug) }}">{{ $post->title }}</a></h3>
                                <p style="font-size:.94rem;margin:0">{{ summarise($post->summary, 220) }}</p>
                                <div class="list-actions">
                                    <a class="btn btn-outline btn-sm" href="{{ route('media.press.show', $post->slug) }}">Read release {!! icon('arrow-right') !!}</a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
                {{ $posts->links('vendor.pagination.epic') }}
            @else
                <x-empty-state title="Press releases will appear here" icon="document">
                    <p style="margin:0">For media enquiries please email
                        <a href="mailto:{{ setting('media_email', setting('contact_email', 'info@epic.org.pk')) }}">{{ setting('media_email', setting('contact_email', 'info@epic.org.pk')) }}</a>.</p>
                </x-empty-state>
            @endif
        </div>
    </section>

    @include('partials.sections')

    <x-cta-band :page="$page" title="Media enquiries"
        text="Our team is available for interviews, expert comment and background briefings on economic policy, human capital, entrepreneurship and responsible AI."
        primary-label="Contact the media desk" />
@endsection
