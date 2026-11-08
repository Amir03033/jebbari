<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use App\Support\MediaSizes;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * One uploaded image. The file lives in Spatie Media Library; content refers to
 * the asset by id so a media choice can be drafted like any other field.
 */
class MediaAsset extends Model implements HasMedia
{
    use AuditsChanges, InteractsWithMedia;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['alt' => 'array', 'is_decorative' => 'boolean'];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')
            ->singleFile()
            ->acceptsMimeTypes(MediaSizes::mimes());
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Small admin thumbnail, created immediately so the upload shows up at once.
        $this->addMediaConversion('thumb')
            ->performOnCollections('file')
            ->format('webp')
            ->width(MediaSizes::THUMB)
            ->nonQueued();

        // Responsive set: every width in WebP, and AVIF where the server can encode it.
        // The original is the fallback.
        // Widths larger than the original are skipped: upscaling adds bytes, not detail.
        $original = (int) ($media?->getCustomProperty('width') ?? 0);
        $widths = array_values(array_filter(MediaSizes::WIDTHS, fn (int $w) => $original === 0 || $w <= $original)) ?: [MediaSizes::WIDTHS[0]];

        foreach ($widths as $width) {
            foreach (MediaSizes::formats() as $format) {
                $this->addMediaConversion("{$format}-{$width}")
                    ->performOnCollections('file')
                    ->format($format)
                    ->width($width);
            }
        }
    }

    public function file(): ?Media
    {
        return $this->getFirstMedia('file');
    }

    public function thumbUrl(): ?string
    {
        $media = $this->file();

        return $media?->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media?->getUrl();
    }

    /** @return list<string> locales ("NL", "EN") that lack alt text */
    public function missingAlt(): array
    {
        if ($this->is_decorative) {
            return [];
        }

        $alt = $this->alt ?? [];

        return collect(['nl' => 'NL', 'en' => 'EN'])
            ->reject(fn ($label, $locale) => filled($alt[$locale] ?? null))
            ->values()
            ->all();
    }

    /**
     * Where this asset is referenced (live values and unpublished drafts), so it
     * cannot be deleted while in use. References are any value stored under a
     * key such as image_id, cover_media_id or media_id.
     *
     * @return list<string>
     */
    public function usages(): array
    {
        $id = (string) $this->getKey();
        $found = [];

        foreach ([Service::class, Project::class, Post::class, Page::class] as $model) {
            foreach ($model::query()->get() as $record) {
                $live = $record->getAttributes();
                $draft = $record->draft_data ?? [];

                foreach ([$live, $draft] as $i => $data) {
                    foreach ($data as $key => $raw) {
                        $value = is_string($raw) && in_array($raw[0] ?? '', ['{', '['], true) ? json_decode($raw, true) : $raw;

                        if (self::referencesId($key, $value, $id)) {
                            $found[] = class_basename($model).($i === 1 ? ' (concept)' : '').': '.($record->t('title', 'nl') ?? $record->getKey());
                            break;
                        }
                    }
                }
            }
        }

        return array_values(array_unique($found));
    }

    private static function referencesId(string|int $key, mixed $value, string $id): bool
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                if (self::referencesId($k, $v, $id)) {
                    return true;
                }
            }

            return false;
        }

        return is_scalar($value)
            && (string) $value === $id
            && is_string($key)
            && (bool) preg_match('/(^|_)(media|image)_id$/', $key);
    }
}
