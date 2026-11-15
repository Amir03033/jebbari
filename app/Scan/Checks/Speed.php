<?php

namespace App\Scan\Checks;

use App\Scan\Check;
use App\Scan\Context;

/**
 * Lab values from PageSpeed Insights (mobile) where available; otherwise only
 * what one request can tell: server response time and an estimate from the HTML.
 */
class Speed
{
    public static function run(Context $c): array
    {
        $psi = $c->pagespeed;
        $checks = [];

        if ($psi !== null && $psi['lcp'] !== null) {
            $checks[] = Check::make('lcp', Check::grade($psi['lcp'], 2500, 4000), null, ['value' => number_format($psi['lcp'] / 1000, 1, ',', '.')]);
        } else {
            $checks[] = Check::make('lcp', Check::SKIPPED);
        }

        if ($psi !== null && $psi['cls'] !== null) {
            $checks[] = Check::make('cls', Check::grade($psi['cls'], 0.1, 0.25), null, ['value' => number_format($psi['cls'], 2, ',', '.')]);
        } else {
            $checks[] = Check::make('cls', Check::SKIPPED);
        }

        if ($psi !== null && $psi['tbt'] !== null) {
            $checks[] = Check::make('tbt', Check::grade($psi['tbt'], 200, 600), null, ['value' => (int) round($psi['tbt'])]);
        } else {
            $checks[] = Check::make('tbt', Check::SKIPPED);
        }

        $checks[] = Check::make('response_time', Check::grade($c->page->ms, 800, 1800), null, ['value' => $c->page->ms]);

        if ($psi !== null && $psi['bytes'] !== null) {
            $mb = $psi['bytes'] / 1_048_576;
            $checks[] = Check::make('page_weight', Check::grade($mb, 1.6, 3.5), null, ['value' => number_format($mb, 1, ',', '.')]);
        } else {
            $kb = strlen($c->page->body) / 1024;
            $checks[] = Check::make('page_weight', Check::grade($kb, 100, 300), 'html', ['value' => (int) round($kb)]);
        }

        $requests = $psi['requests'] ?? null;

        if ($requests !== null) {
            $checks[] = Check::make('requests', Check::grade($requests, 50, 100), null, ['value' => $requests]);
        } else {
            $estimate = count($c->html->all('//script[@src] | //link[@rel="stylesheet"] | //img | //iframe | //video | //link[@rel="preload"]')) + 1;
            $checks[] = Check::make('requests', Check::grade($estimate, 50, 100), 'estimate', ['value' => $estimate]);
        }

        return $checks;
    }

    /**
     * PageSpeed's own performance score. Without it there is no score: one
     * request cannot say how fast a page feels, so the report says "not measured".
     */
    public static function score(Context $c): ?int
    {
        return $c->pagespeed['performance'] ?? null;
    }
}
