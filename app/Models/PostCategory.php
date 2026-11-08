<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostCategory extends Model
{
    use AuditsChanges, Translatable;

    protected $guarded = ['id'];

    protected string $slugSource = 'name';

    protected function casts(): array
    {
        return ['name' => 'array'];
    }

    public function requiredTranslations(): array
    {
        return ['name' => 'Naam'];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'category_id');
    }
}
