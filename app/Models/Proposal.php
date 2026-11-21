<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A personal proposal for one client, made in the admin and shared as a private
 * link (/nl/voorstel/{token}, /en/proposal/{token}). The client reads it and
 * accepts or declines it there. After a decision it can no longer be edited.
 */
class Proposal extends Model
{
    public const STATES = [
        'draft' => 'Concept',
        'sent' => 'Verstuurd',
        'viewed' => 'Bekeken',
        'accepted' => 'Akkoord',
        'declined' => 'Afgewezen',
        'expired' => 'Verlopen',
    ];

    public const STATE_COLORS = ['draft' => 'gray', 'sent' => 'info', 'viewed' => 'warning', 'accepted' => 'success', 'declined' => 'danger', 'expired' => 'gray'];

    /** How a price line repeats. */
    public const PERIODS = ['once' => 'Eenmalig', 'month' => 'Per maand', 'year' => 'Per jaar'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'scope' => 'array',
            'timeline' => 'array',
            'prices' => 'array',
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'first_viewed_at' => 'datetime',
            'last_viewed_at' => 'datetime',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
            'view_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Proposal $proposal) {
            $proposal->token ??= Str::random(40);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function state(): string
    {
        return match (true) {
            $this->accepted_at !== null => 'accepted',
            $this->declined_at !== null => 'declined',
            $this->isExpired() => 'expired',
            $this->first_viewed_at !== null => 'viewed',
            $this->sent_at !== null => 'sent',
            default => 'draft',
        };
    }

    public function isDecided(): bool
    {
        return $this->accepted_at !== null || $this->declined_at !== null;
    }

    public function isExpired(): bool
    {
        return ! $this->isDecided() && $this->valid_until !== null && $this->valid_until->endOfDay()->isPast();
    }

    /** Open for a decision: not decided and not expired. */
    public function isOpen(): bool
    {
        return ! $this->isDecided() && ! $this->isExpired();
    }

    /** @return list<array{description: string, amount: int, period: string}> */
    public function priceLines(): array
    {
        return collect($this->prices ?? [])
            ->filter(fn ($line) => filled($line['description'] ?? null) && is_numeric($line['amount'] ?? null))
            ->map(fn ($line) => ['description' => (string) $line['description'], 'amount' => (int) $line['amount'], 'period' => $line['period'] ?? 'once'])
            ->values()->all();
    }

    /** Sum of the price lines per period, e.g. ['once' => 4500, 'month' => 40]. */
    public function totals(): array
    {
        $sums = collect($this->priceLines())->groupBy('period')->map(fn ($lines) => $lines->sum('amount'));

        // One-off first, then monthly, then yearly.
        return collect(array_keys(self::PERIODS))->filter(fn ($p) => $sums->has($p))->mapWithKeys(fn ($p) => [$p => $sums[$p]])->all();
    }

    public function url(): string
    {
        return route($this->locale.'.proposal.show', ['proposal' => $this->token]);
    }
}
