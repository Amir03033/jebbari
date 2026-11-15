<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** A kind of meeting a visitor can book: online or at a place, with its own length. */
class AppointmentType extends Model
{
    use AuditsChanges;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['name' => 'array', 'location' => 'array', 'is_online' => 'boolean', 'is_active' => 'boolean', 'duration_minutes' => 'integer', 'sort' => 'integer'];
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort')->orderBy('id');
    }
}
