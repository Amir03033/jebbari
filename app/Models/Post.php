<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\HasPublishing;
use App\Models\Concerns\ReferencesMedia;
use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use AuditsChanges, HasPublishing, ReferencesMedia, SoftDeletes, Translatable;

    protected $guarded = ['id'];

    protected string $slugSource = 'title';

    protected array $richText = ['body'];

    protected function casts(): array
    {
        return ['title' => 'array', 'excerpt' => 'array', 'body' => 'array', 'seo' => 'array'];
    }

    public function requiredTranslations(): array
    {
        return ['title' => 'Titel', 'excerpt' => 'Samenvatting', 'body' => 'Artikeltekst'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'cover_media_id');
    }

    public function mediaIds(): array
    {
        return [$this->cover_media_id, $this->getAttribute('seo')['og_media_id'] ?? null];
    }

    public static function previewType(): string
    {
        return 'post';
    }
}
