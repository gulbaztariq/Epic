@php($menu = \App\Models\MenuItem::tree('header'))
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="{{ route('home') }}" aria-label="{{ setting('site_name', 'EPIC') }} home">
            <img src="{{ site_logo() }}" alt="{{ setting('site_name', 'EPIC — Economic Policy and Innovation Centre') }}" width="240" height="122">
        </a>

        <nav class="primary-nav" aria-label="Primary">
            <ul class="nav-list">
                @foreach ($menu as $item)
                    <li class="{{ $item->children->isNotEmpty() ? 'has-drop' : '' }}">
                        <a class="nav-link {{ request()->fullUrlIs($item->resolvedUrl().'*') ? 'is-active' : '' }}"
                           href="{{ $item->resolvedUrl() }}" target="{{ $item->target }}">
                            {{ $item->label }}
                            @if ($item->children->isNotEmpty()) {!! icon('chevron-down') !!} @endif
                        </a>
                        @if ($item->children->isNotEmpty())
                            <ul class="nav-drop">
                                @foreach ($item->children as $child)
                                    <li><a href="{{ $child->resolvedUrl() }}" target="{{ $child->target }}">{{ $child->label }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="header-actions">
            <button class="icon-btn" type="button" data-search-open aria-label="Search the website">{!! icon('search') !!}</button>

            @if (setting('header_cta_label'))
                <a class="btn btn-primary" href="{{ setting('header_cta_url', route('contact')) }}">
                    {{ setting('header_cta_label') }} {!! icon('arrow-right') !!}
                </a>
            @endif

            <button class="icon-btn nav-toggle" type="button" data-nav-open aria-label="Open menu">{!! icon('menu') !!}</button>
        </div>
    </div>
</header>
