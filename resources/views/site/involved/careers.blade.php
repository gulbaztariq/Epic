@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title).' | '.setting('site_name', 'EPIC'))
@section('meta_description', $page->meta_description ?: summarise($page->intro, 155))

@section('content')
    <x-page-hero :page="$page" :breadcrumbs="['Get Involved' => null, $page->title => null]" />

    <section class="section">
        <div class="container">
            @if ($page->intro)
                <p class="lead" style="max-width:76ch">{{ $page->intro }}</p>
            @endif

            @if ($careers->isNotEmpty())
                <div class="grid mt-4" style="gap:18px">
                    @foreach ($careers as $career)
                        <article class="list-card is-wide" style="grid-template-columns:1fr auto;align-items:center">
                            <div class="list-body">
                                <div class="card-meta" style="margin:0">
                                    <span class="badge badge-green">{{ $career->type }}</span>
                                    @if ($career->location)<span>{!! icon('location') !!}{{ $career->location }}</span>@endif
                                    @if ($career->deadline)<span>{!! icon('clock') !!}Apply by {{ $career->deadline->format('d M Y') }}</span>@endif
                                </div>
                                <h3><a href="{{ route('involved.careers.show', $career->slug) }}">{{ $career->title }}</a></h3>
                                @if ($career->summary)<p style="font-size:.94rem;margin:0">{{ summarise($career->summary, 200) }}</p>@endif
                            </div>
                            <a class="btn btn-primary" href="{{ route('involved.careers.show', $career->slug) }}">View role {!! icon('arrow-right') !!}</a>
                        </article>
                    @endforeach
                </div>
            @else
                <x-empty-state title="No open positions right now" icon="briefcase">
                    <p style="margin:0">We are always glad to hear from researchers, analysts and practitioners.
                        Send your CV to <a href="mailto:{{ setting('careers_email', setting('contact_email', 'info@epic.org.pk')) }}">{{ setting('careers_email', setting('contact_email', 'info@epic.org.pk')) }}</a>
                        or <a href="{{ route('involved.volunteer') }}">register your interest</a>.</p>
                </x-empty-state>
            @endif

            @if ($closed->isNotEmpty())
                <div style="margin-top:52px">
                    <h2 class="section-title" style="font-size:1.35rem">Recently closed</h2>
                    <ul class="check-list is-2col">
                        @foreach ($closed as $career)
                            <li>{!! icon('clock') !!}<div><strong>{{ $career->title }}</strong><span>{{ $career->type }} @if ($career->location) &middot; {{ $career->location }} @endif</span></div></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($page->body)
                <div class="prose mt-4">{!! rich($page->body) !!}</div>
            @endif
        </div>
    </section>

    @include('partials.sections')

    <x-cta-band :page="$page" title="Other ways to work with EPIC"
        text="Volunteer, join our research network, or collaborate with us as an institution."
        primary-label="Volunteer with EPIC" :primary-url="route('involved.volunteer')"
        secondary-label="Contact us" :secondary-url="route('contact')" />
@endsection
