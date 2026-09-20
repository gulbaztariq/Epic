<?php

use App\Models\Setting;
use App\Support\Icons;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

if (! function_exists('setting')) {
    /**
     * Read an editable site setting.
     */
    function setting(string $key, ?string $default = null): ?string
    {
        try {
            return Setting::get($key, $default);
        } catch (Throwable) {
            // Database not migrated yet (e.g. during installation).
            return $default;
        }
    }
}

if (! function_exists('uploaded_url')) {
    /**
     * Public URL for a stored upload path, or an external URL passed straight through.
     */
    function uploaded_url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//', 'data:'])) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }
}

if (! function_exists('epic_image')) {
    /**
     * Image URL with a branded placeholder fallback so pages never show broken images.
     */
    function epic_image(?string $path, string $shape = 'card'): string
    {
        $url = uploaded_url($path);

        if ($url) {
            return $url;
        }

        $placeholders = [
            'wide' => 'images/placeholder-wide.svg',
            'card' => 'images/placeholder-card.svg',
            'portrait' => 'images/placeholder-portrait.svg',
            'square' => 'images/placeholder-square.svg',
            'avatar' => 'images/placeholder-avatar.svg',
        ];

        return asset($placeholders[$shape] ?? $placeholders['card']);
    }
}

if (! function_exists('rich')) {
    /**
     * Render admin-entered body copy. Rich-text fields already hold HTML; plain
     * textarea content is converted to paragraphs.
     */
    function rich(?string $content): HtmlString
    {
        if (blank($content)) {
            return new HtmlString('');
        }

        $content = trim($content);

        if (Str::contains($content, ['<p', '<ul', '<ol', '<h1', '<h2', '<h3', '<h4', '<div', '<br', '<blockquote', '<table'])) {
            return new HtmlString($content);
        }

        $paragraphs = preg_split('/\n\s*\n/', $content) ?: [];

        $html = collect($paragraphs)
            ->map(fn ($p) => '<p>'.nl2br(e(trim($p))).'</p>')
            ->implode('');

        return new HtmlString($html);
    }
}

if (! function_exists('icon')) {
    function icon(?string $name, string $class = 'icon', ?string $strokeWidth = null): HtmlString
    {
        return Icons::render($name, $class, $strokeWidth);
    }
}

if (! function_exists('summarise')) {
    function summarise(?string $text, int $length = 150): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $text)) ?? ''), $length);
    }
}

if (! function_exists('social_links')) {
    /**
     * Configured social profiles, ready for the header/footer.
     */
    function social_links(): array
    {
        $map = [
            'social_linkedin' => ['icon' => 'linkedin', 'label' => 'LinkedIn'],
            'social_x' => ['icon' => 'x-social', 'label' => 'X'],
            'social_youtube' => ['icon' => 'youtube', 'label' => 'YouTube'],
            'social_facebook' => ['icon' => 'facebook', 'label' => 'Facebook'],
            'social_instagram' => ['icon' => 'instagram', 'label' => 'Instagram'],
        ];

        $links = [];

        foreach ($map as $key => $meta) {
            $url = setting($key);

            if (filled($url)) {
                $links[] = $meta + ['url' => $url];
            }
        }

        return $links;
    }
}

if (! function_exists('asset_v')) {
    /**
     * Cache-busted asset URL (uses the file's modified time).
     */
    function asset_v(string $path): string
    {
        $full = public_path($path);
        $version = is_file($full) ? filemtime($full) : null;

        return asset($path).($version ? '?v='.$version : '');
    }
}

if (! function_exists('site_logo')) {
    function site_logo(bool $light = false): string
    {
        $key = $light ? 'logo_light' : 'logo';
        $custom = setting($key);

        if (filled($custom)) {
            return uploaded_url($custom);
        }

        return asset($light ? 'images/logo-white.png' : 'images/logo.png');
    }
}
