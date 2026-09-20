@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title).' | '.setting('site_name', 'EPIC'))
@section('meta_description', $page->meta_description ?: summarise($page->intro, 155))

@section('content')
    <x-page-hero :page="$page" :breadcrumbs="['Who We Are' => route('about.index'), $page->title => null]" />

    <section class="section">
        <div class="container">
            @if ($page->intro)
                <p class="lead" style="max-width:76ch">{{ $page->intro }}</p>
            @endif

            @if ($members->isNotEmpty())
                <div class="team-grid mt-4">
                    @foreach ($members as $member)
                        <article class="member-card">
                            <div class="member-photo">
                                @if ($member->photo)
                                    <img src="{{ uploaded_url($member->photo) }}" alt="{{ $member->name }}" loading="lazy">
                                @else
                                    <div class="member-initials">{{ $member->initials }}</div>
                                @endif
                            </div>
                            <div class="member-body">
                                <h3>{{ $member->name }}</h3>
                                @if ($member->designation)<p class="member-role">{{ $member->designation }}</p>@endif
                                @if ($member->short_bio)<p class="member-bio">{{ summarise($member->short_bio, 110) }}</p>@endif
                                @if ($member->email || $member->linkedin)
                                    <div class="member-links">
                                        @if ($member->email)<a href="mailto:{{ $member->email }}" aria-label="Email {{ $member->name }}">{!! icon('mail') !!}</a>@endif
                                        @if ($member->linkedin)<a href="{{ $member->linkedin }}" target="_blank" rel="noopener" aria-label="LinkedIn profile">{!! icon('linkedin') !!}</a>@endif
                                    </div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <x-empty-state title="To be confirmed" icon="users">
                    <p style="margin:0">Details will be announced shortly. For enquiries please
                        <a href="{{ route('contact') }}">contact EPIC</a>.</p>
                </x-empty-state>
            @endif

            @if (isset($areas) && $areas->isNotEmpty())
                <div class="mt-4" style="margin-top:52px">
                    <h2 class="section-title" style="font-size:1.45rem">{{ $areasHeading ?? 'Areas of oversight' }}</h2>
                    <x-check-list :items="$areas" :columns="3" />
                </div>
            @endif

            @if ($page->body)
                <div class="prose mt-4">{!! rich($page->body) !!}</div>
            @endif
        </div>
    </section>

    @include('partials.sections')

    <x-cta-band :page="$page" title="Join the EPIC network"
        text="We welcome researchers, practitioners and institutions who share our commitment to evidence and impact."
        primary-label="Get involved" :primary-url="route('involved.volunteer')"
        secondary-label="See open roles" :secondary-url="route('involved.careers')" />
@endsection
