@extends('layouts.site')

@section('title', $career->title.' | Careers | '.setting('site_name', 'EPIC'))
@section('meta_description', summarise($career->summary ?: $career->description, 155))

@section('content')
    <x-page-hero
        :title="$career->title"
        :subtitle="$career->summary"
        :eyebrow="$career->type"
        :breadcrumbs="['Get Involved' => null, 'Careers' => route('involved.careers'), $career->title => null]" />

    <section class="section">
        <div class="container">
            <div class="content-layout">
                <div>
                    <div class="prose">{!! rich($career->description) !!}</div>

                    @if ($career->requirements)
                        <h2 class="section-title mt-4" style="font-size:1.4rem">What we are looking for</h2>
                        <div class="prose">{!! rich($career->requirements) !!}</div>
                    @endif
                </div>

                <aside class="sidebar">
                    <div class="sidebar-box">
                        <h4>Role summary</h4>
                        <ul class="contact-lines" style="gap:14px">
                            <li>{!! icon('briefcase') !!}<div><strong>Type</strong><span>{{ $career->type }}</span></div></li>
                            @if ($career->location)
                                <li>{!! icon('location') !!}<div><strong>Location</strong><span>{{ $career->location }}</span></div></li>
                            @endif
                            @if ($career->deadline)
                                <li>{!! icon('clock') !!}<div><strong>Deadline</strong><span>{{ $career->deadline->format('d M Y') }}</span></div></li>
                            @endif
                        </ul>

                        @if ($career->is_open)
                            @if ($career->apply_url)
                                <a class="btn btn-green btn-block mt-3" href="{{ $career->apply_url }}" target="_blank" rel="noopener">Apply now</a>
                            @elseif ($career->apply_email)
                                <a class="btn btn-green btn-block mt-3" href="mailto:{{ $career->apply_email }}?subject={{ rawurlencode('Application: '.$career->title) }}">Apply by email</a>
                            @else
                                <a class="btn btn-green btn-block mt-3" href="{{ route('contact') }}">Apply</a>
                            @endif
                        @else
                            <p class="text-muted mt-3" style="font-size:.88rem">This position is now closed.</p>
                        @endif
                    </div>

                    @if ($others->isNotEmpty())
                        <div class="sidebar-box">
                            <h4>Other opportunities</h4>
                            <ul>
                                @foreach ($others as $item)
                                    <li><a href="{{ route('involved.careers.show', $item->slug) }}">{{ $item->title }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </section>
@endsection
