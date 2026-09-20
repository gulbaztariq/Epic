@php
    $footerColumns = \App\Models\MenuItem::tree('footer');
    $legal = \App\Models\MenuItem::tree('footer_legal');
@endphp

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="{{ route('home') }}"><img src="{{ site_logo(true) }}" alt="{{ setting('site_name', 'EPIC') }}"></a>
                <p>{{ setting('footer_about', 'Independent research. Practical solutions. A more prosperous Pakistan.') }}</p>

                @if (count(social_links()))
                    <div class="social-row" style="margin-top:18px">
                        @foreach (social_links() as $link)
                            <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $link['label'] }}">{!! icon($link['icon']) !!}</a>
                        @endforeach
                    </div>
                @endif
            </div>

            @foreach ($footerColumns as $column)
                <div class="footer-col">
                    <h4>{{ $column->label }}</h4>
                    <ul>
                        @foreach ($column->children as $child)
                            <li><a href="{{ $child->resolvedUrl() }}" target="{{ $child->target }}">{{ $child->label }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <div class="footer-col">
                <h4>Get in touch</h4>
                <ul class="footer-contact">
                    @if (setting('contact_address'))
                        <li>{!! icon('location') !!}<span>{{ setting('contact_address') }}</span></li>
                    @endif
                    @if (setting('contact_email'))
                        <li>{!! icon('mail') !!}<a href="mailto:{{ setting('contact_email') }}">{{ setting('contact_email') }}</a></li>
                    @endif
                    @if (setting('contact_phone'))
                        <li>{!! icon('phone') !!}<a href="tel:{{ preg_replace('/[^0-9+]/', '', setting('contact_phone')) }}">{{ setting('contact_phone') }}</a></li>
                    @endif
                </ul>

                @if (setting('footer_tagline'))
                    <p class="footer-tagline">{!! nl2br(e(setting('footer_tagline'))) !!}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="container">
        <div class="footer-bottom">
            <p style="margin:0">&copy; {{ date('Y') }} {{ setting('site_name_full', 'Economic Policy and Innovation Centre (EPIC)') }}. {{ setting('footer_rights', 'All rights reserved.') }}</p>
            <ul>
                @foreach ($legal as $item)
                    <li><a href="{{ $item->resolvedUrl() }}">{{ $item->label }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>
</footer>
