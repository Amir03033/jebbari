<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use AuditsChanges;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }
}
