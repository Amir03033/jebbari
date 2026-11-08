<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\HasPublishing;
use App\Models\Concerns\ReferencesMedia;
use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    use AuditsChanges, HasPublishing, ReferencesMedia, Translatable;

    protected $guarded = ['id'];

    protected string $slugSource = 'title';

    protected array $richText = ['built'];

    protected function casts(): array
    {
        return [
            'title' => 'array', 'kind' => 'array', 'brief' => 'array', 'built' => 'array',
            'outcome' => 'array', 'stack' => 'array', 'gallery' => 'array', 'seo' => 'array',
            'is_featured' => 'boolean',
        ];
    }

    public function requiredTranslations(): array
    {
        return ['title' => 'Titel', 'kind' => 'Soort project', 'brief' => 'Opdracht', 'outcome' => 'Resultaat'];
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'cover_media_id');
    }

    public function mediaIds(): array
    {
        return [
            $this->cover_media_id,
            $this->getAttribute('seo')['og_media_id'] ?? null,
            ...collect($this->gallery ?? [])->pluck('media_id')->all(),
        ];
    }

    public static function previewType(): string
    {
        return 'project';
    }
}
