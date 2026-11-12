<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Locale-prefixed routing helpers. Every page has one route per locale, named
 * "{locale}.{page}", with a translated slug where sensible.
 */
class Locale
{
    /** page => [locale => slug] ('' is the locale root) */
    public const PAGES = [
        'home' => ['nl' => '', 'en' => ''],
        'work' => ['nl' => 'werk', 'en' => 'work'],
        'services' => ['nl' => 'diensten', 'en' => 'services'],
        'about' => ['nl' => 'over-mij', 'en' => 'about'],
        'blog' => ['nl' => 'blog', 'en' => 'blog'],
        'contact' => ['nl' => 'contact', 'en' => 'contact'],
    ];

    /** Old paths that moved, answered with a 301 (the NL About page used to be "over-ons"). */
    public const MOVED = ['nl/over-ons' => '/nl/over-mij'];

    /** URL segment that precedes a blog category slug. */
    public const CATEGORY_SEGMENT = ['nl' => 'categorie', 'en' => 'category'];

    /** @return list<string> */
    public static function all(): array
    {
        return config('site.locales');
    }

    public static function current(): string
    {
        return app()->getLocale();
    }

    public static function other(?string $locale = null): string
    {
        $locale ??= self::current();

        return collect(self::all())->first(fn ($l) => $l !== $locale);
    }

    /** First supported language in Accept-Language order, else the default. */
    public static function preferred(Request $request): string
    {
        // Without the header Symfony invents an "en_US" default, so check it first.
        if (! $request->headers->has('Accept-Language')) {
            return config('site.default_locale');
        }

        foreach ($request->getLanguages() as $language) {
            $primary = strtolower(strtok(str_replace('_', '-', $language), '-'));

            if (in_array($primary, self::all(), true)) {
                return $primary;
            }
        }

        return config('site.default_locale');
    }

    public static function slug(string $page, string $locale): string
    {
        return self::PAGES[$page][$locale];
    }

    public static function url(string $page, ?string $locale = null): string
    {
        return route(($locale ?? self::current()).'.'.$page);
    }

    /** A named route in a locale, e.g. route('work.show', ['slug' => ...], 'en'). */
    public static function route(string $name, array $params = [], ?string $locale = null): string
    {
        return route(($locale ?? self::current()).'.'.$name, $params);
    }

    /**
     * Key of the page the current request belongs to ("work" for both the work
     * index and a project), or null (e.g. /_style, legal pages).
     */
    public static function page(): ?string
    {
        $name = Route::currentRouteName();

        if ($name === null || ! str_contains($name, '.')) {
            return null;
        }

        [$locale, $rest] = explode('.', $name, 2);
        $page = explode('.', $rest, 2)[0];

        return in_array($locale, self::all(), true) && isset(self::PAGES[$page]) ? $page : null;
    }

    /** The same page in another locale, falling back to that locale's home. */
    public static function switchUrl(string $locale): string
    {
        return self::url(self::page() ?? 'home', $locale);
    }
}
