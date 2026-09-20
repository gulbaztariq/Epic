@props([
    'title' => 'Work with EPIC',
    'text' => 'Partner with us on research, policy dialogue, capacity building and innovation initiatives.',
    'primaryLabel' => 'Contact us',
    'primaryUrl' => null,
    'secondaryLabel' => null,
    'secondaryUrl' => null,
])

<section class="section">
    <div class="container">
        <div class="cta-band">
            <div>
                <h2>{{ $title }}</h2>
                <p>{{ $text }}</p>
            </div>
            <div class="cta-actions">
                <a class="btn btn-light" href="{{ $primaryUrl ?: route('contact') }}">{{ $primaryLabel }} {!! icon('arrow-right') !!}</a>
                @if ($secondaryLabel)
                    <a class="btn btn-ghost-light" href="{{ $secondaryUrl }}">{{ $secondaryLabel }}</a>
                @endif
            </div>
        </div>
    </div>
</section>
