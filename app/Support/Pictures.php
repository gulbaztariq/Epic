<?php

namespace App\Support;

use App\Models\ImageSetting;
use Illuminate\Support\Str;
use Throwable;

/**
 * How each uploaded picture is fitted into its frame on the public website.
 *
 * An editor picks, per picture, a fit (whole picture, fill and crop, stretch...),
 * a focal point and a zoom. They reach the page as CSS custom properties set
 * inline on the <img>, which the stylesheet applies to every frame in one place
 * and which combine with hover effects instead of fighting them.
 *
 * "Automatic" sets nothing at all, so each frame's own default applies: the whole
 * picture for content, and filling the frame for full-bleed page headers.
 */
class Pictures
{
    /** Value => label, in the order the dashboard offers them. */
    public const FITS = [
        'auto' => 'Automatic',
        'contain' => 'Whole picture, fit inside',
        'cover' => 'Fill the frame, crop edges',
        'fill' => 'Stretch to fill',
        'scale-down' => 'Whole picture, never enlarge',
        'none' => 'Original size',
    ];

    public const MIN_ZOOM = 100;

    public const MAX_ZOOM = 400;

    /** @var array<string, array{fit: ?string, x: int, y: int, zoom: int}>|null */
    private static ?array $loaded = null;

    /**
     * The saved settings for a picture; the defaults when it has none.
     *
     * @return array{fit: ?string, x: int, y: int, zoom: int}
     */
    public static function settings(?string $path): array
    {
        $default = ['fit' => null, 'x' => 50, 'y' => 50, 'zoom' => 100];

        if (blank($path) || ! self::isStoredPath($path)) {
            return $default;
        }

        return self::all()[$path] ?? $default;
    }

    /** True when the picture has any choice other than the defaults. */
    public static function adjusted(?string $path): bool
    {
        return self::settings($path) !== ['fit' => null, 'x' => 50, 'y' => 50, 'zoom' => 100];
    }

    /**
     * The CSS declarations for a picture, e.g. "--fit:cover;--pos:30% 60%;--zoom:1.4".
     * Empty when nothing has been changed, so the frame's own default applies.
     */
    public static function declarations(?string $path): string
    {
        $s = self::settings($path);
        $parts = [];

        if ($s['fit']) {
            $parts[] = '--fit:'.$s['fit'];
        }

        if ($s['x'] !== 50 || $s['y'] !== 50) {
            $parts[] = '--pos:'.$s['x'].'% '.$s['y'].'%';
        }

        if ($s['zoom'] !== 100) {
            $parts[] = '--zoom:'.rtrim(rtrim(number_format($s['zoom'] / 100, 2, '.', ''), '0'), '.');
        }

        return implode(';', $parts);
    }

    /**
     * A complete style="" attribute (or nothing) for an <img>, merged with any
     * declarations the template already wanted on it.
     */
    public static function style(?string $path, string $extra = ''): string
    {
        $css = trim(implode(';', array_filter([trim($extra, "; \t\n"), self::declarations($path)])));

        return $css === '' ? '' : ' style="'.e($css).'"';
    }

    /**
     * Save what an editor chose for a picture. Anything out of range is clamped,
     * anything unknown is ignored, and a picture left at the defaults has no row.
     *
     * @param  array<string, mixed>  $input  keys: fit, x, y, zoom
     */
    public static function save(?string $path, array $input): void
    {
        if (blank($path) || ! self::isStoredPath($path)) {
            return;
        }

        $fit = $input['fit'] ?? null;
        $fit = is_string($fit) && $fit !== 'auto' && array_key_exists($fit, self::FITS) ? $fit : null;

        $values = [
            'fit' => $fit,
            'focus_x' => self::clamp($input['x'] ?? 50, 0, 100, 50),
            'focus_y' => self::clamp($input['y'] ?? 50, 0, 100, 50),
            'zoom' => self::clamp($input['zoom'] ?? 100, self::MIN_ZOOM, self::MAX_ZOOM, 100),
        ];

        if ($values === ['fit' => null, 'focus_x' => 50, 'focus_y' => 50, 'zoom' => 100]) {
            self::forget($path);

            return;
        }

        ImageSetting::updateOrCreate(['path' => $path], $values);
        self::flush();
    }

    /** Drop a picture's settings, for when the picture itself goes away. */
    public static function forget(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        try {
            ImageSetting::where('path', $path)->delete();
        } catch (Throwable) {
            // The table does not exist yet (mid-installation): nothing to forget.
        }

        self::flush();
    }

    /** Forget what was read this request (the next lookup reads it again). */
    public static function flush(): void
    {
        self::$loaded = null;
    }

    /** @return array<string, array{fit: ?string, x: int, y: int, zoom: int}> */
    private static function all(): array
    {
        if (self::$loaded !== null) {
            return self::$loaded;
        }

        try {
            self::$loaded = ImageSetting::all()->mapWithKeys(fn (ImageSetting $row) => [
                $row->path => [
                    'fit' => array_key_exists((string) $row->fit, self::FITS) && $row->fit !== 'auto' ? $row->fit : null,
                    'x' => self::clamp($row->focus_x, 0, 100, 50),
                    'y' => self::clamp($row->focus_y, 0, 100, 50),
                    'zoom' => self::clamp($row->zoom, self::MIN_ZOOM, self::MAX_ZOOM, 100),
                ],
            ])->all();
        } catch (Throwable) {
            // Not migrated yet: every picture simply uses its frame's default.
            self::$loaded = [];
        }

        return self::$loaded;
    }

    /** Only pictures stored by this site can be adjusted, never external links. */
    private static function isStoredPath(string $path): bool
    {
        return ! Str::startsWith($path, ['http://', 'https://', '//', 'data:']) && strlen($path) <= 255;
    }

    private static function clamp(mixed $value, int $min, int $max, int $default): int
    {
        return is_numeric($value) ? max($min, min($max, (int) round((float) $value))) : $default;
    }
}
