<?php

namespace App\Scan;

use App\Models\Scan;

/** Turns stored check results into the copy shown on the report page and in the mail. */
final class Report
{
    /** What was found: the variant's text, else the shared "text" of the check. */
    public static function found(array $check): string
    {
        $base = 'scan.check.'.$check['key'];
        $key = trans()->has($base.'.'.$check['variant']) ? $base.'.'.$check['variant'] : $base.'.text';

        return __($key, $check['params'] ?? []);
    }

    public static function title(array $check): string
    {
        return __('scan.check.'.$check['key'].'.title');
    }

    public static function why(array $check): string
    {
        return __('scan.check.'.$check['key'].'.why');
    }

    public static function advice(array $check): string
    {
        return __('scan.check.'.$check['key'].'.advice');
    }

    public static function needsWork(array $check): bool
    {
        return in_array($check['status'], [Check::BAD, Check::WARNING], true);
    }

    /**
     * The most important things to fix: every "bad" first, then warnings, in
     * category order, at most $limit, each once (alt text counts once).
     *
     * @return list<array>
     */
    public static function priorities(Scan $scan, int $limit = 5): array
    {
        $all = [];
        foreach (Scan::CATEGORIES as $category) {
            foreach ($scan->results[$category] ?? [] as $check) {
                $all[] = $check + ['category' => $category];
            }
        }

        $seen = [];
        $picked = [];
        foreach ([Check::BAD, Check::WARNING] as $status) {
            foreach ($all as $check) {
                $family = $check['key'] === 'alt_seo' ? 'alt' : $check['key'];

                if ($check['status'] === $status && ! isset($seen[$family])) {
                    $seen[$family] = true;
                    $picked[] = $check;
                }
            }
        }

        return array_slice($picked, 0, $limit);
    }

    /** Coarse step (0 to 10) of a score, for the bar width in CSS (no inline style). */
    public static function step(?int $score): int
    {
        return $score === null ? 0 : min(10, max(0, (int) round($score / 10)));
    }

    /** good / warning / bad for a 0 to 100 score, the same bands as Lighthouse. */
    public static function band(?int $score): string
    {
        return match (true) {
            $score === null => Check::SKIPPED,
            $score >= 90 => Check::GOOD,
            $score >= 50 => Check::WARNING,
            default => Check::BAD,
        };
    }

    /** Prefilled contact message for "Let jebbari fix this": the scanned URL from ?scan=, if it is one. */
    public static function contactMessage(?string $url): ?string
    {
        $url = (string) $url;

        return strlen($url) <= 2048 && filter_var($url, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $url)
            ? __('scan.report.contact_message', ['url' => $url])
            : null;
    }
}
