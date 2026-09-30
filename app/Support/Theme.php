<?php

namespace App\Support;

/**
 * The look-and-feel choices an editor can make from Settings → Appearance.
 *
 * They reach the stylesheet as CSS custom properties. Whatever is stored is
 * validated again here before it is written into a page, so a bad or tampered
 * value falls back to the default and can never inject CSS.
 */
class Theme
{
    public const DEFAULT_MENU_BACKGROUND = '#e8f1fa';

    /** How strongly the navy wash covers a page-header picture, in percent. */
    public const DEFAULT_HEADER_OVERLAY = 50;

    public const MAX_HEADER_OVERLAY = 95;

    public static function menuBackground(): string
    {
        $value = (string) setting('menu_background', self::DEFAULT_MENU_BACKGROUND);

        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : self::DEFAULT_MENU_BACKGROUND;
    }

    public static function headerOverlay(): int
    {
        $value = setting('page_header_overlay', (string) self::DEFAULT_HEADER_OVERLAY);

        return is_numeric($value)
            ? max(0, min(self::MAX_HEADER_OVERLAY, (int) round((float) $value)))
            : self::DEFAULT_HEADER_OVERLAY;
    }

    /** The declarations for the :root rule. */
    public static function css(): string
    {
        return sprintf(
            '--menu-bg:%s;--hero-overlay:%s;',
            self::menuBackground(),
            rtrim(rtrim(number_format(self::headerOverlay() / 100, 2, '.', ''), '0'), '.') ?: '0'
        );
    }
}
