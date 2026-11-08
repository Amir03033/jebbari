<?php

namespace App\Support;

class MediaSizes
{
    /** Responsive widths generated for every format. */
    public const WIDTHS = [480, 768, 1200, 1600, 2400];

    public const THUMB = 320;

    public const MAX_KB = 10240;

    public const MIN_WIDTH = 400;

    public const MAX_WIDTH = 6000;

    private static ?bool $avif = null;

    /**
     * Whether this server can really encode AVIF. gd_info() can claim support
     * while encoding yields nothing (seen on a stock PHP build), so it is
     * tested by encoding a tiny image. Without it, WebP is the only modern
     * format and the original is the fallback.
     */
    public static function avifSupported(): bool
    {
        return self::$avif ??= (function (): bool {
            if (! function_exists('imageavif')) {
                return false;
            }

            $image = imagecreatetruecolor(8, 8);
            ob_start();
            $ok = @imageavif($image, null, 50);
            $bytes = (string) ob_get_clean();

            return $ok && $bytes !== '';
        })();
    }

    /** @return list<string> */
    public static function formats(): array
    {
        return self::avifSupported() ? ['webp', 'avif'] : ['webp'];
    }

    /** @return list<string> */
    public static function mimes(): array
    {
        return ['image/jpeg', 'image/png', 'image/webp', ...(self::avifSupported() ? ['image/avif'] : [])];
    }
}
