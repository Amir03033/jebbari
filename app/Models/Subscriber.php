<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** A newsletter sign-up. It counts only once the address has been confirmed by email. */
class Subscriber extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime', 'unsubscribed_at' => 'datetime'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotNull('confirmed_at')->whereNull('unsubscribed_at');
    }

    public function state(): string
    {
        return match (true) {
            $this->unsubscribed_at !== null => 'Afgemeld',
            $this->confirmed_at !== null => 'Bevestigd',
            default => 'Wacht op bevestiging',
        };
    }
}
