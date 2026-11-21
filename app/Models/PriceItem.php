<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One line of the price calculator. Groups: project (pick one, required),
 * size (pick one), extra (any number), deadline (pick one). Prices are whole
 * euros, a range from min to max. Only active items with both prices show.
 */
class PriceItem extends Model
{
    use AuditsChanges;

    public const GROUPS = ['project' => 'Projecttype', 'size' => 'Omvang', 'extra' => 'Extra', 'deadline' => 'Deadline'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['name' => 'array', 'description' => 'array', 'is_active' => 'boolean', 'price_min' => 'integer', 'price_max' => 'integer', 'sort' => 'integer'];
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNotNull('price_min')->whereNotNull('price_max')->orderBy('sort')->orderBy('id');
    }
}
