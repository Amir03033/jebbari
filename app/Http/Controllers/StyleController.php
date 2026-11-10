<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class StyleController extends Controller
{
    private const TOKENS = [
        'paper' => ['#F5F3EE', 'Default background'],
        'paper-2' => ['#ECE9E2', 'Subtle surfaces, image placeholders'],
        'ink' => ['#0E0E0F', 'Primary text, dark sections'],
        'ink-2' => ['#686763', 'Secondary text'],
        'signal' => ['#2B4EF5', 'The one accent'],
    ];

    public function __invoke(): View
    {
        abort_if(app()->isProduction(), 404);

        $paper = self::TOKENS['paper'][0];

        // Contrast ratios are computed here, not typed in, so the page stays true.
        $pairs = [
            ['ink on paper', '#0E0E0F', $paper],
            ['ink-2 on paper', '#686763', $paper],
            ['ink-2 on paper-2', '#686763', '#ECE9E2'],
            ['signal on paper (focus ring, non-text)', '#2B4EF5', $paper],
            ['paper on ink', $paper, '#0E0E0F'],
            ['muted on ink (paper at 64%)', $this->blend($paper, '#0E0E0F', 0.64), '#0E0E0F'],
            ['ink on signal (text selection)', '#0E0E0F', '#2B4EF5'],
        ];

        return view('pages.style', [
            'tokens' => self::TOKENS,
            'contrast' => array_map(fn ($p) => [$p[0], round($this->ratio($p[1], $p[2]), 2)], $pairs),
        ]);
    }

    private function luminance(string $hex): float
    {
        [$r, $g, $b] = array_map(
            fn ($c) => ($c /= 255) <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4,
            sscanf(ltrim($hex, '#'), '%02x%02x%02x'),
        );

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    private function ratio(string $a, string $b): float
    {
        [$hi, $lo] = [max($this->luminance($a), $this->luminance($b)), min($this->luminance($a), $this->luminance($b))];

        return ($hi + 0.05) / ($lo + 0.05);
    }

    private function blend(string $fg, string $bg, float $alpha): string
    {
        $f = sscanf(ltrim($fg, '#'), '%02x%02x%02x');
        $b = sscanf(ltrim($bg, '#'), '%02x%02x%02x');

        return sprintf('#%02x%02x%02x', ...array_map(fn ($x, $y) => (int) round($x * $alpha + $y * (1 - $alpha)), $f, $b));
    }
}
