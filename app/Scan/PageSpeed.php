<?php

namespace App\Scan;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Google PageSpeed Insights, mobile strategy. Returns only the numbers the
 * report uses, as a plain array, or null when the API is off or fails (the
 * report then says speed could only be estimated).
 */
class PageSpeed
{
    private const ENDPOINT = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    /** @return array{performance: ?int, lcp: ?float, cls: ?float, tbt: ?float, bytes: ?float, requests: ?int, contrast: ?array}|null */
    public function run(string $url): ?array
    {
        if (! config('scan.pagespeed.enabled')) {
            return null;
        }

        // Repeated "category" parameters, which the query array syntax cannot express.
        $query = http_build_query(array_filter(['url' => $url, 'strategy' => 'mobile', 'key' => config('scan.pagespeed.key')]))
            .'&category=performance&category=accessibility';

        try {
            $response = Http::timeout((int) config('scan.pagespeed.timeout'))->acceptJson()->get(self::ENDPOINT.'?'.$query);
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        if (! $response->successful()) {
            logger()->warning('PageSpeed Insights failed', ['status' => $response->status()]);

            return null;
        }

        $lh = $response->json('lighthouseResult');

        if (! is_array($lh)) {
            return null;
        }

        $audit = fn (string $id) => $lh['audits'][$id] ?? null;
        $num = fn (string $id) => isset($lh['audits'][$id]['numericValue']) ? (float) $lh['audits'][$id]['numericValue'] : null;
        $score = $lh['categories']['performance']['score'] ?? null;
        $contrast = $audit('color-contrast');

        return [
            'performance' => $score === null ? null : (int) round($score * 100),
            'lcp' => $num('largest-contentful-paint'),
            'cls' => $num('cumulative-layout-shift'),
            'tbt' => $num('total-blocking-time'),
            'bytes' => $num('total-byte-weight'),
            'requests' => isset($audit('network-requests')['details']['items']) ? count($audit('network-requests')['details']['items']) : null,
            'contrast' => $contrast && array_key_exists('score', $contrast) && $contrast['score'] !== null
                ? ['passed' => (float) $contrast['score'] >= 1, 'count' => count($contrast['details']['items'] ?? [])]
                : null,
        ];
    }
}
