<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Draws typographic share images with GD: dark surface, the title in Geist,
 * the wordmark and the accent dot. 1200 x 630, cached by content.
 */
class ShareImage
{
    private const W = 1200;

    private const H = 630;

    public static function available(): bool
    {
        return function_exists('imagettftext') && is_file(self::font());
    }

    /** Absolute path of the image, or null when this server cannot draw one. */
    public static function for(string $key, string $title, string $label): ?string
    {
        if (! self::available()) {
            return null;
        }

        $dir = storage_path('app/share');
        $path = $dir.'/'.$key.'-'.substr(sha1($title.'|'.$label.'|v2'), 0, 12).'.png';

        if (! is_file($path)) {
            File::ensureDirectoryExists($dir);
            self::draw($path, $title, $label);
        }

        return $path;
    }

    public static function draw(string $path, string $title, string $label): void
    {
        $im = imagecreatetruecolor(self::W, self::H);
        $bg = imagecolorallocate($im, 11, 11, 12);
        $fg = imagecolorallocate($im, 244, 243, 239);
        $muted = imagecolorallocate($im, 157, 155, 149);
        $accent = imagecolorallocate($im, 126, 149, 255);
        $font = self::font();

        imagefill($im, 0, 0, $bg);
        imagealphablending($im, true);

        // A soft pool of light in the upper right, built from translucent circles.
        for ($r = 520; $r > 0; $r -= 8) {
            imagefilledellipse($im, 1080, 40, $r * 2, $r * 2, imagecolorallocatealpha($im, 72, 100, 255, 126));
        }

        imagettftext($im, 20, 0, 80, 110, $muted, $font, mb_strtoupper($label));

        // The title: as large as fits in three lines.
        foreach ([84, 72, 60, 50] as $size) {
            $lines = self::wrap($title, $size, $font, self::W - 160);
            if (count($lines) <= 3) {
                break;
            }
        }

        $lines = array_slice($lines, 0, 3);
        $y = self::H - 150 - (count($lines) - 1) * (int) ($size * 1.12);

        foreach ($lines as $line) {
            imagettftext($im, $size, 0, 76, $y, $fg, $font, $line);
            $y += (int) ($size * 1.12);
        }

        $box = imagettftext($im, 30, 0, 80, self::H - 60, $fg, $font, mb_strtolower(config('site.name')));
        imagefilledellipse($im, $box[2] + 12, self::H - 66, 12, 12, $accent);

        imagepng($im, $path, 6);
    }

    /** @return list<string> */
    private static function wrap(string $text, int $size, string $font, int $width): array
    {
        $lines = [];
        $line = '';

        foreach (preg_split('/\s+/', trim($text)) as $word) {
            $try = trim($line.' '.$word);
            $box = imagettfbbox($size, 0, $font, $try);

            if ($width < $box[2] - $box[0] && $line !== '') {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $try;
            }
        }

        return [...$lines, $line];
    }

    private static function font(): string
    {
        return resource_path('fonts/geist-latin-500.ttf');
    }
}
