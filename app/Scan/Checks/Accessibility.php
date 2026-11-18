<?php

namespace App\Scan\Checks;

use App\Scan\Check;
use App\Scan\Context;
use DOMElement;

/** Basic accessibility checks. Not a WCAG audit: the report says so. */
class Accessibility
{
    public static function run(Context $c): array
    {
        return [
            self::labels($c),
            Seo::alt($c, 'alt', Check::BAD),
            self::headings($c),
            self::viewport($c),
            self::contrast($c),
        ];
    }

    private static function labels(Context $c): array
    {
        $fields = $c->html->all('//input[not(@type) or not(contains("hidden submit button image reset", translate(@type,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")))] | //select | //textarea');
        $labelled = [];

        foreach ($c->html->all('//label[@for]') as $label) {
            $labelled[$label->getAttribute('for')] = true;
        }

        $missing = count(array_filter($fields, fn (DOMElement $f) => ! (
            ($f->getAttribute('id') !== '' && isset($labelled[$f->getAttribute('id')]))
            || filled($f->getAttribute('aria-label'))
            || filled($f->getAttribute('aria-labelledby'))
            || filled($f->getAttribute('title'))
            || self::insideLabel($f)
        )));

        return match (true) {
            $fields === [] => Check::make('form_labels', Check::GOOD, 'none'),
            $missing === 0 => Check::make('form_labels', Check::GOOD, null, ['total' => count($fields)]),
            default => Check::make('form_labels', Check::BAD, null, ['count' => $missing, 'total' => count($fields)]),
        };
    }

    private static function insideLabel(DOMElement $el): bool
    {
        for ($node = $el->parentNode; $node instanceof DOMElement; $node = $node->parentNode) {
            if ($node->tagName === 'label') {
                return true;
            }
        }

        return false;
    }

    private static function headings(Context $c): array
    {
        $levels = array_map(fn (DOMElement $h) => (int) substr($h->tagName, 1), $c->html->all('//h1 | //h2 | //h3 | //h4 | //h5 | //h6'));

        if ($levels === []) {
            return Check::make('headings', Check::WARNING, 'none');
        }

        $previous = 0;
        foreach ($levels as $level) {
            if ($level > $previous + 1 && $previous > 0) {
                return Check::make('headings', Check::WARNING, 'skipped', ['from' => 'h'.$previous, 'to' => 'h'.$level]);
            }

            $previous = $level;
        }

        return Check::make('headings', Check::GOOD);
    }

    private static function viewport(Context $c): array
    {
        $viewport = strtolower(str_replace(' ', '', (string) $c->html->meta('viewport')));

        if (! str_contains($viewport, 'width=device-width')) {
            return Check::make('viewport', Check::BAD);
        }

        $max = preg_match('/maximum-scale=([\d.]+)/', $viewport, $m) ? (float) $m[1] : null;

        return str_contains($viewport, 'user-scalable=no') || str_contains($viewport, 'user-scalable=0') || ($max !== null && $max < 2)
            ? Check::make('viewport', Check::WARNING, 'zoom')
            : Check::make('viewport', Check::GOOD);
    }

    /** From PageSpeed's contrast audit when it ran, else from inline colours (an indication only). */
    private static function contrast(Context $c): array
    {
        $audit = $c->pagespeed['contrast'] ?? null;

        if ($audit !== null) {
            return $audit['passed']
                ? Check::make('contrast', Check::GOOD)
                : Check::make('contrast', Check::BAD, null, ['count' => $audit['count']]);
        }

        $pairs = 0;
        $low = 0;

        foreach ($c->html->all('//*[@style]') as $el) {
            $style = strtolower($el->getAttribute('style'));
            $fg = preg_match('/(?:^|;)\s*color\s*:\s*([^;]+)/', $style, $a) ? Contrast::parse($a[1]) : null;
            $bg = preg_match('/background(?:-color)?\s*:\s*([^;]+)/', $style, $b) ? Contrast::parse($b[1]) : null;

            if ($fg && $bg) {
                $pairs++;
                $low += Contrast::ratio($fg, $bg) < 4.5 ? 1 : 0;
            }
        }

        return match (true) {
            $pairs === 0 => Check::make('contrast', Check::SKIPPED),
            $low === 0 => Check::make('contrast', Check::GOOD, 'inline'),
            default => Check::make('contrast', Check::WARNING, 'inline_low', ['count' => $low]),
        };
    }
}
