<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/** A booked meeting. Times are stored in UTC and shown in Europe/Amsterdam. */
class Appointment extends Model
{
    use Prunable;

    public const CONFIRMED = 'confirmed';

    public const CANCELLED = 'cancelled';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'reminder_sent_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (Appointment $appointment) {
            $appointment->token ??= Str::random(40);
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

    public function type(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class, 'appointment_type_id');
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', self::CONFIRMED);
    }

    public function prunable(): Builder
    {
        return static::query()->where('ends_at', '<', now()->subDays(config('leads.appointment_days')));
    }

    public function localStart(): Carbon
    {
        return $this->starts_at->copy()->timezone(config('site.timezone'));
    }

    public function isUpcoming(): bool
    {
        return $this->status === self::CONFIRMED && $this->starts_at->isFuture();
    }

    public static function slotKey(CarbonInterface $start): string
    {
        return $start->copy()->utc()->format('Y-m-d\TH:i');
    }
}
