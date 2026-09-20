@php($menu = \App\Models\MenuItem::tree('header'))
<div class="nav-backdrop" aria-hidden="true"></div>

<nav class="mobile-nav" aria-label="Mobile">
    <div class="mobile-nav-head">
        <a href="{{ route('home') }}"><img src="{{ site_logo() }}" alt="{{ setting('site_name', 'EPIC') }}"></a>
        <button class="icon-btn" type="button" data-nav-close aria-label="Close menu">{!! icon('close') !!}</button>
    </div>

    <ul>
        @foreach ($menu as $item)
            <li>
                @if ($item->children->isNotEmpty())
                    <button class="m-parent" type="button">{{ $item->label }} {!! icon('chevron-down') !!}</button>
                    <ul class="m-sub">
                        <li><a href="{{ $item->resolvedUrl() }}">{{ $item->label }} overview</a></li>
                        @foreach ($item->children as $child)
                            <li><a href="{{ $child->resolvedUrl() }}">{{ $child->label }}</a></li>
                        @endforeach
                    </ul>
                @else
                    <a href="{{ $item->resolvedUrl() }}">{{ $item->label }}</a>
                @endif
            </li>
        @endforeach
    </ul>

    <div class="mobile-nav-actions">
        @if (setting('header_cta_label'))
            <a class="btn btn-primary btn-block" href="{{ setting('header_cta_url', route('contact')) }}">{{ setting('header_cta_label') }}</a>
        @endif
        <a class="btn btn-outline btn-block" href="{{ route('contact') }}">Contact EPIC</a>
    </div>
</nav>
