@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title).' | '.setting('site_name', 'EPIC'))
@section('meta_description', $page->meta_description ?: summarise($page->intro, 155))

@section('content')
    <x-page-hero :page="$page" :breadcrumbs="['What We Do' => route('work.themes'), $page->title => null]" />

    <section class="section">
        <div class="container">
            @if ($page->intro)
                <p class="lead" style="max-width:76ch">{{ $page->intro }}</p>
            @endif

            @if ($chapters->isNotEmpty())
                <div class="chapter-grid mt-4">
                    @foreach ($chapters as $chapter)
                        <article class="chapter-card">
                            <div class="chapter-top">
                                @if ($chapter->image)
                                    <img src="{{ uploaded_url($chapter->image) }}" alt="{{ $chapter->country }}" style="width:38px;height:26px;object-fit:cover;border-radius:3px">
                                @else
                                    {!! icon('globe') !!}
                                @endif
                                <h3>{{ $chapter->country }}</h3>
                            </div>
                            @if ($chapter->city)<p><strong style="color:var(--navy)">{{ $chapter->city }}</strong></p>@endif
                            @if ($chapter->description)<p>{{ $chapter->description }}</p>@endif
                            <span class="badge {{ $chapter->status === 'active' ? 'badge-green' : 'badge-outline' }}" style="align-self:flex-start;margin-top:auto">
                                {{ \App\Models\Chapter::STATUSES[$chapter->status] ?? $chapter->status }}
                            </span>
                            @if ($chapter->contact_email)
                                <a class="section-link" href="mailto:{{ $chapter->contact_email }}">{!! icon('mail') !!} {{ $chapter->contact_email }}</a>
                            @endif
                        </article>
                    @endforeach
                </div>
            @else
                <x-empty-state title="Chapters coming soon" icon="globe" />
            @endif

            @if ($page->body)
                <div class="prose mt-4">{!! rich($page->body) !!}</div>
            @endif
        </div>
    </section>

    @include('partials.sections')

    <x-cta-band title="Start an EPIC chapter"
        text="We welcome expressions of interest from institutions and professionals who would like to host an EPIC international chapter."
        primary-label="Express interest" />
@endsection
