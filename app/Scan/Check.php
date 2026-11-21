<?php

namespace App\Scan;

/**
 * One check result as a plain array (stored as JSON): the copy lives in
 * lang scan.check.{key}: title, one text per variant and an advice.
 */
final class Check
{
    public const GOOD = 'good';

    public const WARNING = 'warning';

    public const BAD = 'bad';

    /** Could not be measured; not counted in the score. */
    public const SKIPPED = 'skipped';

    /** @return array{key: string, status: string, variant: string, params: array<string, scalar>} */
    public static function make(string $key, string $status, ?string $variant = null, array $params = []): array
    {
        return ['key' => $key, 'status' => $status, 'variant' => $variant ?? $status, 'params' => $params];
    }

    /** 0 to 100 from good (1), warning (0.5) and bad (0); null when nothing was measured. */
    public static function score(array $checks): ?int
    {
        $points = ['good' => 1, 'warning' => 0.5, 'bad' => 0];
        $counted = array_filter($checks, fn ($c) => isset($points[$c['status']]));

        if ($counted === []) {
            return null;
        }

        return (int) round(array_sum(array_map(fn ($c) => $points[$c['status']], $counted)) / count($counted) * 100);
    }

    /** Status by thresholds: at or below $good is good, at or below $warn a warning, else bad. */
    public static function grade(float $value, float $good, float $warn): string
    {
        return $value <= $good ? self::GOOD : ($value <= $warn ? self::WARNING : self::BAD);
    }
}
