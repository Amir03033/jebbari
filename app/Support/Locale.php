<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Locale-prefixed routing helpers. Every page has one route per locale,
 * named "{locale}.{page}", with a translated slug where sensible.
 */
class Locale
{
    /** page => [locale => slug] ('' is the locale root) */
    public const PAGES = [
        'home' => ['nl' => '', 'en' => ''],
        'work' => ['nl' => 'werk', 'en' => 'work'],
        'services' => ['nl' => 'diensten', 'en' => 'services'],
        'about' => ['nl' => 'over-ons', 'en' => 'about'],
        'contact' => ['nl' => 'contact', 'en' => 'contact'],
    ];

    /** @return list<string> */
    public static function all(): array
    {
        return config('site.locales');
    }

    public static function current(): string
    {
        return app()->getLocale();
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

    /** Name of the page the current request is on, or null (e.g. /_style). */
    public static function page(): ?string
    {
        $name = Route::currentRouteName();

        if ($name === null || ! str_contains($name, '.')) {
            return null;
        }

        [$locale, $page] = explode('.', $name, 2);

        return in_array($locale, self::all(), true) && isset(self::PAGES[$page]) ? $page : null;
    }

    /** The same page in another locale, falling back to that locale's home. */
    public static function switchUrl(string $locale): string
    {
        return self::url(self::page() ?? 'home', $locale);
    }
}
