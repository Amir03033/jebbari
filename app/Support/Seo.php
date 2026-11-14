<?php

namespace App\Support;

use App\Models\MediaAsset;
use Illuminate\Database\Eloquent\Model;

/**
 * Title, description, canonical, robots, share image and JSON-LD for one page,
 * taken from the record's own SEO fields in the CMS and falling back to the
 * site-wide SEO settings.
 */
class Seo
{
    public const DEFAULT_IMAGE = 'og/default.png';

    /**
     * @param  array{
     *   record?: ?Model, title?: ?string, description?: ?string, canonical: string,
     *   alternates?: array<string, string>, image?: ?MediaAsset, type?: string,
     *   noindex?: bool, jsonld?: list<array>, raw_title?: bool, share?: ?string
     * }  $in
     * @return array<string, mixed>
     */
    public static function make(array $in): array
    {
        $record = $in['record'] ?? null;
        $own = is_array($record?->getAttribute('seo')) ? $record->getAttribute('seo') : [];
        $locale = app()->getLocale();

        $site = Settings::get('seo.site_name') ?: config('site.name');
        $template = Settings::get('seo.title_template') ?: '{title} · '.$site;

        $title = loc($own['title'] ?? null)
            ?: (($in['raw_title'] ?? false) ? ($in['title'] ?? $site) : str_replace('{title}', $in['title'] ?? $site, $template));

        $description = loc($own['description'] ?? null)
            ?: ($in['description'] ?? null)
            ?: loc(Settings::get('seo.default_description'))
            ?: __('site.meta_description');

        $image = isset($own['og_media_id']) ? MediaAsset::find($own['og_media_id']) : null;
        $image ??= $in['image'] ?? null;
        $image ??= ($id = Settings::get('seo.default_og_media_id')) ? MediaAsset::find($id) : null;
        // No image chosen: a generated title card where the page has one, else the site default.
        $imageUrl = $image?->shareUrl() ?: (ShareImage::available() ? ($in['share'] ?? null) : null) ?: asset(self::DEFAULT_IMAGE);

        $alternates = $in['alternates'] ?? [];
        $other = Locale::other($locale);

        return [
            'title' => strip_tags($title),
            'description' => mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($description))), 0, 300),
            'canonical' => $in['canonical'],
            'alternates' => $alternates,
            'xDefault' => $alternates[config('site.default_locale')] ?? null,
            'robots' => ($in['noindex'] ?? false) || ($own['noindex'] ?? false) ? 'noindex, nofollow' : null,
            'image' => str_starts_with($imageUrl, 'http') ? $imageUrl : url($imageUrl),
            'imageAlt' => $image?->altFor() ?: null,
            'type' => $in['type'] ?? 'website',
            'locale' => $locale === 'nl' ? 'nl_NL' : 'en_GB',
            'localeAlternates' => isset($alternates[$other]) ? [$other === 'nl' ? 'nl_NL' : 'en_GB'] : [],
            'siteName' => $site,
            // Organization, website and founder on every page, then the page's own data.
            'jsonld' => [...StructuredData::global(), ...($in['jsonld'] ?? [])],
        ];
    }
}
