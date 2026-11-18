<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/** One measurement of a live project: did it answer, and how fast (docs/UPTIME.md). */
class UptimeCheck extends Model
{
    use Prunable;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['ok' => 'boolean', 'status' => 'integer', 'ms' => 'integer', 'checked_at' => 'datetime'];
    }

    public function prunable(): Builder
    {
        return static::query()->where('checked_at', '<', now()->subDays(90));
    }
}
