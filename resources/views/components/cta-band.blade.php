@props([
    'page' => null,
    'title' => 'Work with EPIC',
    'text' => 'Partner with us on research, policy dialogue, capacity building and innovation initiatives.',
    'primaryLabel' => 'Contact us',
    'primaryUrl' => null,
    'secondaryLabel' => null,
    'secondaryUrl' => null,
])

@php
    // A "Call to action band" section on the page overrides the defaults,
    // so editors can change this text from the dashboard.
    $block = $page?->section('cta');

    $bandTitle = $block?->heading ?: $title;
    $bandText = $block?->body ? strip_tags($block->body) : $text;
    $bandPrimaryLabel = $block?->link_text ?: $primaryLabel;
    $bandPrimaryUrl = $block?->link_url ?: ($primaryUrl ?: route('contact'));
@endphp

<section class="section">
    <div class="container">
        <div class="cta-band">
            <div>
                <h2>{{ $bandTitle }}</h2>
                <p>{{ $bandText }}</p>
            </div>
            <div class="cta-actions">
                <a class="btn btn-light" href="{{ $bandPrimaryUrl }}">{{ $bandPrimaryLabel }} {!! icon('arrow-right') !!}</a>
                @if ($secondaryLabel)
                    <a class="btn btn-ghost-light" href="{{ $secondaryUrl }}">{{ $secondaryLabel }}</a>
                @endif
            </div>
        </div>
    </div>
</section>
