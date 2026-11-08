<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\HasPublishing;
use App\Models\Concerns\ReferencesMedia;
use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use AuditsChanges, HasPublishing, ReferencesMedia, Translatable;

    protected $guarded = ['id'];

    protected string $slugSource = 'title';

    protected array $richText = ['description'];

    protected function casts(): array
    {
        return [
            'title' => 'array', 'summary' => 'array', 'description' => 'array', 'benefits' => 'array',
            'included' => 'array', 'not_included' => 'array', 'timeline' => 'array',
            'tags' => 'array', 'packages' => 'array', 'seo' => 'array',
        ];
    }

    public function requiredTranslations(): array
    {
        return ['title' => 'Titel', 'summary' => 'Samenvatting'];
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'image_id');
    }

    public function mediaIds(): array
    {
        return [$this->image_id, $this->getAttribute('seo')['og_media_id'] ?? null];
    }

    public static function previewType(): string
    {
        return 'service';
    }
}
