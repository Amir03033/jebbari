<?php

namespace App\Models\Concerns;

use App\Support\HtmlSanitizer;
use Illuminate\Support\Str;

/**
 * Dutch and English content in one JSON column: {"nl": "...", "en": "..."}.
 * Slugs are plain columns slug_nl / slug_en so lookups stay simple.
 *
 * Models declare:
 *   requiredTranslations(): array  field => Dutch label (needed before publishing)
 *   protected array $richText      translatable fields that hold HTML
 *   protected string $slugSource   field the slugs are generated from (optional)
 */
trait Translatable
{
    public const LOCALES = ['nl', 'en'];

    public static function bootTranslatable(): void
    {
        static::saving(function ($model) {
            $model->sanitizeRichText();
            $model->ensureSlugs();
        });
    }

    /** @return array<string, string> */
    public function requiredTranslations(): array
    {
        return [];
    }

    public function t(string $field, ?string $locale = null): mixed
    {
        $value = $this->getAttribute($field);

        return is_array($value) ? ($value[$locale ?? app()->getLocale()] ?? null) : $value;
    }

    public function slugFor(string $locale): ?string
    {
        return $this->getAttribute('slug_'.$locale);
    }

    /**
     * Missing required fields per language. With $withDraft the unpublished
     * changes are considered (what publishing would check); without it only
     * the live values count (what the public site may show).
     *
     * @return array<string, list<string>> locale => labels
     */
    public function translationGaps(bool $withDraft = true): array
    {
        $gaps = [];

        foreach (self::LOCALES as $locale) {
            $gaps[$locale] = $this->gapsFor($locale, $withDraft);
        }

        return $gaps;
    }

    /** @return list<string> */
    public function gapsFor(string $locale, bool $withDraft = true): array
    {
        $source = $withDraft && method_exists($this, 'withDraft') ? $this->withDraft() : $this;
        $gaps = [];

        foreach ($source->requiredTranslations() as $field => $label) {
            if (! self::filled($source->t($field, $locale))) {
                $gaps[] = $label;
            }
        }

        if (method_exists($source, 'blockGaps')) {
            $gaps = [...$gaps, ...$source->blockGaps($locale)];
        }

        return $gaps;
    }

    /** Complete in this language using live values only: the test for showing it publicly. */
    public function isTranslatedIn(string $locale): bool
    {
        return $this->gapsFor($locale, false) === [];
    }

    public function isFullyTranslated(): bool
    {
        return collect($this->translationGaps())->flatten()->isEmpty();
    }

    public static function filled(mixed $value): bool
    {
        return is_string($value) && trim(html_entity_decode(strip_tags($value))) !== '';
    }

    public function sanitizeRichText(): void
    {
        foreach ($this->richText ?? [] as $field) {
            $value = $this->getAttribute($field);

            if (is_array($value)) {
                $this->setAttribute($field, array_map(fn ($html) => HtmlSanitizer::clean($html), $value));
            }
        }
    }

    public function ensureSlugs(): void
    {
        $source = method_exists($this, 'slugSource') ? $this->slugSource() : ($this->slugSource ?? null);

        if (! $source) {
            return;
        }

        foreach (self::LOCALES as $locale) {
            $column = 'slug_'.$locale;
            $title = $this->t($source, $locale);

            if (blank($this->getAttribute($column)) && is_string($title) && trim($title) !== '') {
                $this->setAttribute($column, $this->uniqueSlug($column, Str::slug($title)));
            }
        }
    }

    private function uniqueSlug(string $column, string $base): string
    {
        $base = $base !== '' ? $base : 'item';
        $slug = $base;
        $i = 2;

        while (static::query()
            ->where($column, $slug)
            ->when($this->exists, fn ($q) => $q->whereKeyNot($this->getKey()))
            ->when(method_exists($this, 'bootSoftDeletes'), fn ($q) => $q->withoutGlobalScopes())
            ->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
