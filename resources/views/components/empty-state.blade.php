@props(['title' => 'Coming soon', 'icon' => 'sparkle'])

<div class="empty-state">
    {!! icon($icon) !!}
    <h3>{{ $title }}</h3>
    <div>{{ $slot }}</div>
</div>
