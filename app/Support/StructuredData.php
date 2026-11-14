<?php

namespace App\Support;

use App\Content\Site;
use App\Models\Post;
use App\Models\Project;
use App\Models\Service;

/**
 * schema.org JSON-LD. Built only from facts in the CMS: nothing is added to
 * make the business look bigger (no ratings, no reviews, no addresses that were
 * not provided).
 */
class StructuredData
{
    /** Stable identifiers, the same on every page and in both languages, so the entities link up. */
    public static function id(string $entity): string
    {
        return rtrim(url('/'), '/').'/#'.$entity;
    }

    /** A reference to an entity described elsewhere in the graph. */
    public static function ref(string $entity): array
    {
        return ['@id' => self::id($entity)];
    }

    /** Organization, website and founder: on every public page. */
    public static function base(): array
    {
        return [self::organization(Locale::url('home')), self::website(Locale::url('home')), self::person()];
    }

    public static function organization(string $homeUrl): array
    {
        $company = Settings::group('company');
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'ProfessionalService',
            '@id' => self::id('org'),
            'name' => Settings::get('seo.site_name') ?: config('site.name'),
            'url' => $homeUrl,
            'description' => loc(Settings::get('seo.default_description')) ?: __('site.meta_description'),
            'founder' => self::ref('amir'),
            'location' => [
                ['@type' => 'Place', 'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Amsterdam', 'addressCountry' => 'NL']],
                ['@type' => 'Place', 'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Zwolle', 'addressCountry' => 'NL']],
            ],
            'areaServed' => [
                ['@type' => 'Country', 'name' => 'Netherlands'],
                ['@type' => 'City', 'name' => 'Zwolle'],
                ['@type' => 'City', 'name' => 'Amsterdam'],
            ],
        ];

        if (filled($company['legal_name'] ?? null)) {
            $data['legalName'] = $company['legal_name'];
        }

        if (filled($company['btw'] ?? null)) {
            $data['vatID'] = $company['btw'];
        }

        if ($email = Site::email()) {
            $data['email'] = $email;
        }

        if ($social = array_column(Site::social(), 'url')) {
            $data['sameAs'] = $social;
        }

        return $data;
    }

    public static function website(string $homeUrl): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => self::id('website'),
            'name' => Settings::get('seo.site_name') ?: config('site.name'),
            'url' => $homeUrl,
            'publisher' => self::ref('org'),
            'inLanguage' => Locale::all(),
        ];
    }

    /** The founder. Facts from config/site.php only; never education. */
    public static function person(): array
    {
        $person = config('site.person');
        $locale = app()->getLocale();

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            '@id' => self::id('amir'),
            'name' => $person['name'],
            'url' => Locale::url('about'),
            'jobTitle' => $person['job_title'][$locale] ?? null,
            'description' => $person['description'][$locale] ?? null,
            'worksFor' => self::ref('org'),
            'homeLocation' => ['@type' => 'City', 'name' => $person['home_location'], 'containedInPlace' => ['@type' => 'Country', 'name' => 'Netherlands']],
            'knowsAbout' => $person['knows_about'],
            'sameAs' => array_values(array_filter($person['same_as'])) ?: null,
        ], fn ($v) => $v !== null && $v !== []);
    }

    /** The About page: a profile page about the founder. */
    public static function profilePage(string $url, string $name): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            '@id' => $url.'#page',
            'url' => $url,
            'name' => $name,
            'inLanguage' => app()->getLocale(),
            'isPartOf' => self::ref('website'),
            'mainEntity' => self::ref('amir'),
            'about' => self::ref('amir'),
        ];
    }

    /** @param  list<array{name: string, url: string}>  $items */
    public static function breadcrumbs(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn ($item, $i) => [
                '@type' => 'ListItem', 'position' => $i + 1, 'name' => $item['name'], 'item' => $item['url'],
            ])->all(),
        ];
    }

    public static function article(Post $post, string $url, ?string $image): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => loc($post->title),
            'description' => loc($post->excerpt),
            'inLanguage' => app()->getLocale(),
            'mainEntityOfPage' => $url,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'author' => ['@type' => 'Person', '@id' => self::id('amir'), 'name' => config('site.person.name'), 'url' => Locale::url('about')],
            'publisher' => ['@type' => 'Organization', '@id' => self::id('org'), 'name' => Settings::get('seo.site_name') ?: config('site.name')],
        ];

        return array_filter($data + ($image ? ['image' => $image] : []), fn ($v) => $v !== null && $v !== '');
    }

    public static function service(Service $service, string $url): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => loc($service->title),
            'description' => loc($service->summary),
            'url' => $url,
            'inLanguage' => app()->getLocale(),
            'areaServed' => ['@type' => 'Country', 'name' => 'Netherlands'],
            'provider' => ['@type' => 'ProfessionalService', '@id' => self::id('org'), 'name' => Settings::get('seo.site_name') ?: config('site.name')],
        ], fn ($v) => $v !== null && $v !== '');
    }

    public static function project(Project $project, string $url, ?string $image): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'CreativeWork',
            'name' => loc($project->title),
            'description' => loc($project->summary) ?: loc($project->brief),
            'url' => $url,
            'inLanguage' => app()->getLocale(),
            'genre' => loc($project->kind),
            'keywords' => $project->stack ? implode(', ', $project->stack) : null,
            'dateCreated' => $project->year ? (string) $project->year : null,
            'image' => $image,
            'creator' => ['@type' => 'Person', '@id' => self::id('amir'), 'name' => config('site.person.name')],
            'publisher' => ['@type' => 'ProfessionalService', '@id' => self::id('org'), 'name' => Settings::get('seo.site_name') ?: config('site.name')],
        ], fn ($v) => $v !== null && $v !== '');
    }
}
