@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title).' | '.setting('site_name', 'EPIC'))
@section('meta_description', $page->meta_description ?: summarise($page->intro, 155))

@section('content')
    <x-page-hero :page="$page" :breadcrumbs="['Partnerships & MoUs' => route('partnerships.index'), $page->title => null]" />

    <section class="section">
        <div class="container">
            @if ($page->intro)
                <p class="lead" style="max-width:76ch">{{ $page->intro }}</p>
            @endif

            @if ($partners->isNotEmpty())
                <div class="grid grid-3 mt-4">
                    @foreach ($partners as $partner)
                        <article class="card" style="padding:24px">
                            @if ($partner->logo)
                                <img src="{{ uploaded_url($partner->logo) }}" alt="{{ $partner->name }}" style="max-height:50px;width:auto;margin-bottom:14px">
                            @else
                                <div class="principle-icon" style="margin-bottom:14px">{!! icon('document') !!}</div>
                            @endif
                            <h3 style="font-size:1.08rem">{{ $partner->name }}</h3>
                            @if ($partner->country)<p class="text-muted" style="font-size:.85rem;margin:0 0 6px">{{ $partner->country }}</p>@endif
                            @if ($partner->description)<p style="font-size:.9rem">{{ $partner->description }}</p>@endif
                            @if ($partner->signed_on)
                                <span class="badge badge-blue" style="align-self:flex-start">Signed {{ $partner->signed_on->format('M Y') }}</span>
                            @endif
                        </article>
                    @endforeach
                </div>
            @else
                <x-empty-state title="MoUs will be listed here" icon="document">
                    <p style="margin:0">EPIC enters into strategic Memoranda of Understanding with institutions that share our interests
                        in research, education, policy, entrepreneurship, innovation and human capital development.</p>
                </x-empty-state>
            @endif

            @if ($scope->isNotEmpty())
                <div style="margin-top:54px">
                    <h2 class="section-title" style="font-size:1.45rem">MoU partnerships include</h2>
                    <x-check-list :items="$scope" :columns="3" />
                </div>
            @endif
        </div>
    </section>

    @include('partials.sections')

    <x-cta-band :page="$page" title="Sign an MoU with EPIC"
        text="Joint research, faculty collaboration, internships, co-publication, collaborative grant applications and more."
        primary-label="Discuss an MoU" />
@endsection
