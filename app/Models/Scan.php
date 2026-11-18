<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * One run of the free website check. Results are stored as plain arrays of
 * check keys and statuses; the copy comes from lang/{nl,en}/scan.php when the
 * report is shown, so a report reads in the visitor's language.
 */
class Scan extends Model
{
    use Prunable;

    public const PENDING = 'pending';

    public const RUNNING = 'running';

    public const DONE = 'done';

    public const FAILED = 'failed';

    public const CATEGORIES = ['security', 'seo', 'speed', 'accessibility'];

    /** A scan still waiting after this long is reported as failed. */
    public const STUCK_AFTER_MINUTES = 10;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'scores' => 'array',
            'results' => 'array',
            'contact_consent' => 'boolean',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Scan $scan) {
            $scan->token ??= Str::random(40);
        });
    }

    /** Set when this scan is a monthly run of a website watch. */
    public function watch(): BelongsTo
    {
        return $this->belongsTo(ScanWatch::class, 'scan_watch_id');
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays(config('scan.retention_days')));
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::DONE, self::FAILED], true);
    }

    /** Pending or running for too long: the job never ran or died. */
    public function isStuck(): bool
    {
        return ! $this->isFinished() && $this->created_at->lt(now()->subMinutes(self::STUCK_AFTER_MINUTES));
    }

    /** Average of the category scores that could be measured, or null. */
    public function overall(): ?int
    {
        $scores = array_filter($this->scores ?? [], fn ($s) => $s !== null);

        return $scores ? (int) round(array_sum($scores) / count($scores)) : null;
    }
}
