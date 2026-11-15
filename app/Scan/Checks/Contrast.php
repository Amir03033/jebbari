<?php

namespace App\Scan\Checks;

/** WCAG contrast ratio for simple CSS colours (hex, rgb(), a few names). */
final class Contrast
{
    private const NAMES = ['black' => [0, 0, 0], 'white' => [255, 255, 255], 'red' => [255, 0, 0], 'gray' => [128, 128, 128], 'grey' => [128, 128, 128], 'silver' => [192, 192, 192], 'yellow' => [255, 255, 0], 'blue' => [0, 0, 255], 'green' => [0, 128, 0]];

    /** @return array{0: int, 1: int, 2: int}|null */
    public static function parse(string $value): ?array
    {
        $value = strtolower(trim($value));

        if (preg_match('/#([0-9a-f]{3}|[0-9a-f]{6})\b/', $value, $m)) {
            $hex = strlen($m[1]) === 3 ? preg_replace('/(.)/', '$1$1', $m[1]) : $m[1];

            return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
        }

        if (preg_match('/rgba?\(\s*(\d+)[\s,]+(\d+)[\s,]+(\d+)\s*(?:[,\/]\s*([\d.]+%?))?\s*\)/', $value, $m)) {
            // Semi-transparent colours depend on what is behind them: not judged.
            if (isset($m[4]) && $m[4] !== '' && (float) $m[4] < 1 && ! str_ends_with($m[4], '%')) {
                return null;
            }

            return [min(255, (int) $m[1]), min(255, (int) $m[2]), min(255, (int) $m[3])];
        }

        return self::NAMES[trim($value)] ?? null;
    }

    public static function ratio(array $a, array $b): float
    {
        [$l1, $l2] = [self::luminance($a), self::luminance($b)];

        return (max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05);
    }

    private static function luminance(array $rgb): float
    {
        $c = array_map(function ($v) {
            $v /= 255;

            return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }, $rgb);

        return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
    }
}
