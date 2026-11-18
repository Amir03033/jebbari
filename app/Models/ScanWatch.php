<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * "Keep an eye on my website": after a free check a visitor can ask for a new
 * check every month (double opt-in). Each run is an ordinary Scan linked to the
 * watch; the mail compares it with the previous one (docs/SCAN.md, Website watch).
 * Unsubscribing deletes the row; unconfirmed sign-ups go after a week.
 */
class ScanWatch extends Model
{
    use Prunable;

    /** At most this many watched sites per email address. */
    public const MAX_PER_EMAIL = 3;

    /** A category that drops this many points (or the overall score by half of it) counts as worse. */
    public const DROP = 10;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'last_scores' => 'array',
            'confirmed_at' => 'datetime',
            'next_run_at' => 'datetime',
            'last_run_at' => 'datetime',
            'runs' => 'integer',
        ];
    }

    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }

    public function lastScan(): BelongsTo
    {
        return $this->belongsTo(Scan::class, 'last_scan_id');
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->whereNotNull('confirmed_at')->where('next_run_at', '<=', now());
    }

    public function prunable(): Builder
    {
        return static::query()->whereNull('confirmed_at')->where('created_at', '<', now()->subDays(7));
    }

    /**
     * Change per category against the previous run: [category => [now, before, diff]].
     *
     * @return array<string, array{0: ?int, 1: ?int, 2: ?int}>
     */
    public function compare(array $scores): array
    {
        $before = $this->last_scores ?? [];

        return collect(Scan::CATEGORIES)->mapWithKeys(function ($category) use ($scores, $before) {
            $now = $scores[$category] ?? null;
            $was = $before[$category] ?? null;

            return [$category => [$now, $was, $now !== null && $was !== null ? $now - $was : null]];
        })->all();
    }

    /** Whether the new scores are clearly worse than last time. */
    public function isWorse(array $scores): bool
    {
        $changes = collect($this->compare($scores));

        if ($changes->contains(fn ($c) => $c[2] !== null && $c[2] <= -self::DROP)) {
            return true;
        }

        $avg = fn (array $s) => ($v = array_filter($s, fn ($x) => $x !== null)) ? array_sum($v) / count($v) : null;
        [$now, $was] = [$avg($scores), $avg($this->last_scores ?? [])];

        return $now !== null && $was !== null && $now - $was <= -self::DROP / 2;
    }
}
