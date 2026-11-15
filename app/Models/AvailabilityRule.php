<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Opening hours for appointments, per weekday (Europe/Amsterdam). No type means: every type. */
class AvailabilityRule extends Model
{
    use AuditsChanges;

    public const WEEKDAYS = [1 => 'Maandag', 2 => 'Dinsdag', 3 => 'Woensdag', 4 => 'Donderdag', 5 => 'Vrijdag', 6 => 'Zaterdag', 7 => 'Zondag'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['weekday' => 'integer'];
    }

    public function appointmentType(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class);
    }
}
