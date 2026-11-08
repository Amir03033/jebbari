<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;

class NavigationItem extends Model
{
    use AuditsChanges, Translatable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['label' => 'array', 'is_visible' => 'boolean'];
    }
}
