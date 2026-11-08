<?php

namespace App\Support;

use App\Content\Blocks\Blocks;
use App\Models\MediaAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

/**
 * Renders any content record (with its unpublished changes applied) as a plain,
 * readable structure in one language. This is the preview until the real page
 * layouts exist; those will render through the same signed URL.
 */
class PreviewData
{
    private const SKIP = ['id', 'status', 'published_at', 'draft_data', 'created_at', 'updated_at', 'deleted_at', 'slug_nl', 'slug_en', 'seo', 'sort', 'featured_sort'];

    public static function html(Model $record, string $locale): HtmlString
    {
        $out = '<dl class="grid gap-8">';

        foreach ($record->getAttributes() as $key => $_) {
            if (in_array($key, self::SKIP, true)) {
                continue;
            }

            $value = $record->getAttribute($key);

            if ($key === 'blocks') {
                $out .= self::row('Onderdelen', self::blocks($record, $value ?? [], $locale));

                continue;
            }

            $out .= self::row(self::label($key), self::render($value, $key, $locale));
        }

        if ($seo = $record->getAttribute('seo')) {
            $out .= self::row('SEO', self::render($seo, 'seo', $locale));
        }

        return new HtmlString($out.'</dl>');
    }

    private static function row(string $label, string $body): string
    {
        return '<div class="border-t border-line pt-4"><dt class="t-label text-muted">'.e($label).'</dt><dd class="mt-2">'.$body.'</dd></div>';
    }

    private static function blocks(Model $page, array $blocks, string $locale): string
    {
        if (! $blocks) {
            return '<span class="text-muted">Geen onderdelen</span>';
        }

        $out = '<ol class="grid gap-6">';

        foreach ($blocks as $block) {
            $definition = Blocks::find($page->key, $block['type'] ?? '');
            $visible = ($block['data']['visible'] ?? true) !== false;
            $out .= '<li class="border border-line p-4"><p class="t-label '.($visible ? '' : 'text-muted').'">'
                .e($definition?->label() ?? $block['type'] ?? '?').($visible ? '' : ' · verborgen').'</p>'
                .'<div class="mt-3">'.self::render(array_diff_key($block['data'] ?? [], ['visible' => 1]), 'data', $locale).'</div></li>';
        }

        return $out.'</ol>';
    }

    private static function render(mixed $value, string $key, string $locale): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '<span class="text-muted">—</span>';
        }

        if (is_bool($value)) {
            return $value ? 'Ja' : 'Nee';
        }

        if (is_array($value) && self::isTranslation($value)) {
            $text = $value[$locale] ?? null;

            if (! is_string($text) || trim(strip_tags($text)) === '') {
                return '<span class="t-label">Ontbreekt in '.strtoupper($locale).'</span>';
            }

            return $text !== strip_tags($text)
                ? '<div class="t-body">'.HtmlSanitizer::clean($text).'</div>'
                : '<span style="white-space: pre-line">'.e($text).'</span>';
        }

        if (is_array($value)) {
            if (array_is_list($value)) {
                return '<ol class="grid gap-3 pl-5" style="list-style: decimal">'.collect($value)->map(fn ($v) => '<li>'.self::render($v, $key, $locale).'</li>')->implode('').'</ol>';
            }

            $out = '<dl class="grid gap-2">';
            foreach ($value as $k => $v) {
                $out .= '<div><dt class="t-label text-muted">'.e(self::label((string) $k)).'</dt><dd>'.self::render($v, (string) $k, $locale).'</dd></div>';
            }

            return $out.'</dl>';
        }

        if (preg_match('/(^|_)(media|image)_id$/', $key) && is_numeric($value)) {
            $asset = MediaAsset::find($value);

            return $asset ? '<span class="flex items-center gap-3">'.($asset->thumbUrl() ? '<img src="'.e($asset->thumbUrl()).'" alt="" width="96" height="64" class="h-16 w-24 border border-line object-cover">' : '').'<span>'.e($asset->title).'</span></span>' : '<span class="text-muted">Afbeelding niet gevonden</span>';
        }

        return e((string) ($value instanceof \BackedEnum ? $value->value : $value));
    }

    private static function isTranslation(array $value): bool
    {
        return $value !== [] && array_diff(array_keys($value), ['nl', 'en']) === [];
    }

    private static function label(string $key): string
    {
        return ucfirst(str_replace('_', ' ', preg_replace('/_id$/', '', $key)));
    }
}
