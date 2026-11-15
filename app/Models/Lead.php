<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * One prospect across the calculator, the request wizard, appointments and the
 * website check. The token ties the steps together (session, resume link).
 * "choices" holds the answers: calculator, wizard, scan.
 */
class Lead extends Model
{
    use Prunable;

    public const SOURCES = ['calculator' => 'Prijscalculator', 'wizard' => 'Aanvraag', 'appointment' => 'Afspraak', 'scan' => 'Website-check'];

    public const STATUSES = ['new' => 'Nieuw', 'contacted' => 'Benaderd', 'quote' => 'Offerte', 'won' => 'Gewonnen', 'lost' => 'Verloren'];

    public const WIZARD_STEPS = 5;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'choices' => 'array',
            'contact_consent' => 'boolean',
            'submitted_at' => 'datetime',
            'estimate_min' => 'integer',
            'estimate_max' => 'integer',
            'wizard_step' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Lead $lead) {
            $lead->token ??= Str::random(40);
        });
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /** Leads someone can actually be contacted on. */
    public function scopeReachable(Builder $query): Builder
    {
        return $query->whereNotNull('email');
    }

    /** Unfinished, anonymous drafts go after config('leads.draft_days'). */
    public function prunable(): Builder
    {
        return static::query()->whereNull('email')->where('updated_at', '<', now()->subDays(config('leads.draft_days')));
    }

    public function choice(string $path, mixed $default = null): mixed
    {
        return data_get($this->choices ?? [], $path, $default);
    }

    public function setChoice(string $path, mixed $value): void
    {
        $choices = $this->choices ?? [];
        data_set($choices, $path, $value);
        $this->choices = $choices;
    }
}
