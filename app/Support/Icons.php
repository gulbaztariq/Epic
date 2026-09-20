<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * A small, dependency-free inline SVG icon set so the site needs no icon font
 * or build step. Icon names are exposed in the admin UI as a dropdown.
 */
class Icons
{
    /** Icons drawn with strokes (the default style). */
    private const STROKE = [
        'chart' => '<path d="M3 21h18"/><rect x="5" y="12" width="3.2" height="7"/><rect x="10.4" y="7" width="3.2" height="12"/><rect x="15.8" y="3.5" width="3.2" height="15.5"/>',
        'lightbulb' => '<path d="M9.2 18h5.6"/><path d="M10.5 21h3"/><path d="M12 3a6 6 0 0 0-3.6 10.8c.7.5 1.1 1.3 1.1 2.2h5c0-.9.4-1.7 1.1-2.2A6 6 0 0 0 12 3z"/>',
        'people' => '<circle cx="9" cy="8" r="3.2"/><path d="M3 20.5c0-3.3 2.7-5.5 6-5.5s6 2.2 6 5.5"/><circle cx="17.6" cy="9.8" r="2.4"/><path d="M16.2 15.2c2.7.3 4.8 2.3 4.8 5.3"/>',
        'users' => '<circle cx="12" cy="7.5" r="3.2"/><path d="M5.5 20.5c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><path d="M4.5 11.5a2.4 2.4 0 1 0 0-4.8M19.5 11.5a2.4 2.4 0 1 1 0-4.8"/>',
        'bank' => '<path d="M12 3 3 8h18L12 3z"/><path d="M3 8v2h18V8"/><path d="M6 10v8M10 10v8M14 10v8M18 10v8"/><path d="M3 21h18"/>',
        'trending-up' => '<polyline points="3 17 9 11 13 15 21 7"/><polyline points="15 7 21 7 21 13"/>',
        'monitor' => '<rect x="2.5" y="4" width="19" height="12.5" rx="2"/><path d="M8.5 20.5h7M12 16.5v4"/><path d="M7 8.5h5M7 12h3"/>',
        'rocket' => '<path d="M12 3c3.4 1.7 5.6 5.5 5.6 9.5V15l-2.6 3H9l-2.6-3v-2.5C6.4 8.5 8.6 4.7 12 3z"/><circle cx="12" cy="10" r="1.8"/><path d="M9 18.5 7.5 21.5M15 18.5l1.5 3"/>',
        'partnership' => '<circle cx="9" cy="12" r="5.2"/><circle cx="15" cy="12" r="5.2"/>',
        'wifi' => '<path d="M2.5 9.2a14 14 0 0 1 19 0"/><path d="M5.8 12.6a9.5 9.5 0 0 1 12.4 0"/><path d="M9 16a5 5 0 0 1 6 0"/><circle cx="12" cy="19.4" r="1.1"/>',
        'graduation' => '<path d="M2.5 8.6 12 4.2l9.5 4.4L12 13 2.5 8.6z"/><path d="M6.5 10.6V16c0 1.7 2.5 3 5.5 3s5.5-1.3 5.5-3v-5.4"/><path d="M21.5 8.6v5"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3c2.6 2.4 4 5.6 4 9s-1.4 6.6-4 9c-2.6-2.4-4-5.6-4-9s1.4-6.6 4-9z"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'document' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5.5A2 2 0 0 1 11 3.5h2a2 2 0 0 1 2 2V7M3 12.5h18"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m16.2 16.2 4.8 4.8"/>',
        'mail' => '<rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="m3.2 7 8.8 6 8.8-6"/>',
        'phone' => '<path d="M5.5 3h3l2 5-2.5 1.5a11.5 11.5 0 0 0 5.5 5.5L15 12.5l5 2v3a2 2 0 0 1-2.1 2A15.5 15.5 0 0 1 3.5 5.1 2 2 0 0 1 5.5 3z"/>',
        'location' => '<path d="M12 21.5s7-6 7-11.5a7 7 0 1 0-14 0c0 5.5 7 11.5 7 11.5z"/><circle cx="12" cy="10" r="2.5"/>',
        'download' => '<path d="M12 3.5v12M7 11l5 5 5-5M4 20.5h16"/>',
        'play' => '<circle cx="12" cy="12" r="9"/><path d="M10 8.2 16.2 12 10 15.8V8.2z"/>',
        'mic' => '<rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5.5 11a6.5 6.5 0 0 0 13 0M12 17.8V21M8.5 21h7"/>',
        'image' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.6" cy="9.6" r="1.8"/><path d="m4 18.5 5-5 3.5 3.5L16 13.5l4 4"/>',
        'check' => '<path d="m4.5 12.5 5 5 10-11"/>',
        'arrow-right' => '<path d="M4 12h15M13 6l6 6-6 6"/>',
        'arrow-left' => '<path d="M20 12H5M11 6l-6 6 6 6"/>',
        'book' => '<path d="M3 5.5A2.5 2.5 0 0 1 5.5 3H11v18H5.5A2.5 2.5 0 0 1 3 18.5v-13z"/><path d="M21 5.5A2.5 2.5 0 0 0 18.5 3H13v18h5.5A2.5 2.5 0 0 0 21 18.5v-13z"/>',
        'scale' => '<path d="M12 3.5v17M6.5 20.5h11"/><path d="M12 6.5 5 8.5l3 5 4-5zM12 6.5l7 2-3 5-4-5z"/>',
        'shield' => '<path d="M12 3l8 3v6c0 5-3.4 8.4-8 9-4.6-.6-8-4-8-9V6l8-3z"/><path d="m9 12 2.2 2.2L15.5 10"/>',
        'target' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.4"/>',
        'gear' => '<circle cx="12" cy="12" r="3.3"/><path d="M12 2.5v3M12 18.5v3M4.4 4.4l2.1 2.1M17.5 17.5l2.1 2.1M2.5 12h3M18.5 12h3M4.4 19.6l2.1-2.1M17.5 6.5l2.1-2.1"/>',
        'cpu' => '<rect x="6" y="6" width="12" height="12" rx="2"/><rect x="9.6" y="9.6" width="4.8" height="4.8" rx="1"/><path d="M9.5 3v3M14.5 3v3M9.5 18v3M14.5 18v3M3 9.5h3M3 14.5h3M18 9.5h3M18 14.5h3"/>',
        'network' => '<circle cx="6" cy="18" r="2.6"/><circle cx="18" cy="18" r="2.6"/><circle cx="12" cy="5" r="2.6"/><path d="M12 7.6v4.4M10.4 13.2 7.6 16M13.6 13.2 16.4 16"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5.3l3.6 2.1"/>',
        'list' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 3.2h6V6H9zM9 11h6M9 15h6"/>',
        'star' => '<path d="m12 3.5 2.7 5.6 6.1.8-4.5 4.3 1.1 6-5.4-3-5.4 3 1.1-6L3.2 9.9l6.1-.8L12 3.5z"/>',
        'heart' => '<path d="M12 20.5S4.5 16 4.5 10.6A4.6 4.6 0 0 1 12 7a4.6 4.6 0 0 1 7.5 3.6C19.5 16 12 20.5 12 20.5z"/>',
        'pie' => '<circle cx="12" cy="12" r="9"/><path d="M12 3v9h9"/>',
        'leaf' => '<path d="M4.5 19.5C4.5 11 9.5 4.5 20 4.5c0 9.5-5.5 15-15.5 15z"/><path d="M4.5 19.5c3-4.5 6.5-7.5 11-9.5"/>',
        'layers' => '<path d="m12 3 9 4.5-9 4.5L3 7.5 12 3z"/><path d="m3 12 9 4.5L21 12"/><path d="m3 16.5 9 4.5 9-4.5"/>',
        'edit' => '<path d="M13 5.5 18.5 11M4 20h4l11-11a2.5 2.5 0 0 0-3.5-3.5L4.5 16.5 4 20z"/>',
        'trash' => '<path d="M4 7h16M9 7V4.5h6V7M6 7l1 13.5h10L18 7M10 11v6M14 11v6"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'eye' => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3.2"/>',
        'logout' => '<path d="M10 4.5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h4"/><path d="M15 8l4 4-4 4M9.5 12H19"/>',
        'grid' => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>',
        'upload' => '<path d="M12 16.5V4.5M7 9.5l5-5 5 5M4 20.5h16"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'linkedin' => '<rect x="3" y="3" width="18" height="18" rx="3.5"/><path d="M7.5 10.5V17M7.5 7.3v.1M11.5 17v-4.2a2.6 2.6 0 0 1 5.2 0V17"/>',
        'youtube' => '<rect x="2.5" y="6" width="19" height="12" rx="3.6"/><path d="M10.5 9.4 15.8 12l-5.3 2.6V9.4z"/>',
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.9" r=".9"/>',
        'chevron-down' => '<path d="m6 9.5 6 6 6-6"/>',
        'chevron-right' => '<path d="m9.5 6 6 6-6 6"/>',
        'inbox' => '<path d="M3 13h5l1.5 2.5h5L16 13h5"/><path d="M3 13 5.5 5h13L21 13v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5z"/>',
        'flag' => '<path d="M5 21V4.5M5 5h10l-1.2 3.5L15 12H5"/>',
        'handshake' => '<path d="M2.5 11.5 6 8l3.5 3 2.5-2 2.5 2L18 8l3.5 3.5"/><path d="M6 8v6.5l4 4a2 2 0 0 0 2.8 0l.7-.7 1.3 1.3a1.8 1.8 0 0 0 2.6-2.6L18 14.5V8"/>',
        'sparkle' => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3z"/><path d="M18.5 16.5l.8 2.2 2.2.8-2.2.8-.8 2.2-.8-2.2-2.2-.8 2.2-.8.8-2.2z"/>',
    ];

    /** Icons drawn as solid shapes. */
    private const FILLED = [
        'quote' => '<path d="M7 6.5c-2.3 0-4.2 1.9-4.2 4.2S4.7 14.9 7 14.9c-.1 2-1.2 3.4-3.3 4.4 4.7-.4 7.4-3.6 7.4-8.6 0-2.3-1.8-4.2-4.1-4.2zM18.6 6.5c-2.3 0-4.2 1.9-4.2 4.2s1.9 4.2 4.2 4.2c-.1 2-1.2 3.4-3.3 4.4 4.7-.4 7.4-3.6 7.4-8.6 0-2.3-1.8-4.2-4.1-4.2z"/>',
        'x-social' => '<path d="M4 3h4.3l4 5.6L16.9 3H21l-6.4 7.5L21.4 21H17l-4.3-6-5 6H3.6l6.7-7.9L4 3z"/>',
        'facebook' => '<path d="M13.2 21v-8h2.9l.5-3.2h-3.4V7.9c0-.9.3-1.5 1.7-1.5h1.8V3.5c-.8-.1-1.9-.2-3-.2-2.8 0-4.6 1.6-4.6 4.4v2.1H6.9V13h2.2v8h4.1z"/>',
    ];

    public static function names(): array
    {
        return collect(array_keys(self::STROKE))
            ->merge(array_keys(self::FILLED))
            ->sort()
            ->values()
            ->all();
    }

    public static function has(?string $name): bool
    {
        return $name !== null && (isset(self::STROKE[$name]) || isset(self::FILLED[$name]));
    }

    public static function render(?string $name, string $class = '', ?string $strokeWidth = null): HtmlString
    {
        $name = $name && self::has($name) ? $name : 'chart';
        $class = $class !== '' ? ' class="'.e($class).'"' : '';

        if (isset(self::FILLED[$name])) {
            return new HtmlString(
                '<svg'.$class.' viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">'.self::FILLED[$name].'</svg>'
            );
        }

        $width = $strokeWidth ?: '1.7';

        return new HtmlString(
            '<svg'.$class.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="'.e($width).'"'
            .' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.self::STROKE[$name].'</svg>'
        );
    }
}
