<?php

namespace App\Support;

use App\Content\Site;
use App\Models\Post;
use App\Models\Project;
use App\Models\Service;

/**
 * schema.org JSON-LD. Built only from facts in the CMS: nothing is added to
 * make the business look bigger (no ratings, no reviews). The only address is
 * the one in config/site.php; no VAT number is published.
 */
class StructuredData
{
    /** Stable identifiers, the same in every language, so entities can point at each other. */
    public static function id(string $entity): string
    {
        return rtrim(url('/'), '/').'/#'.$entity;
    }

    private static function siteName(): string
    {
        return Settings::get('seo.site_name') ?: config('site.name');
    }

    /** Organization, website and founder: printed on every public page. */
    public static function global(): array
    {
        $home = Locale::url('home');

        return [self::organization($home), self::website($home), self::person()];
    }

    public static function organization(string $homeUrl): array
    {
        $company = Settings::group('company');
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'ProfessionalService',
            '@id' => self::id('org'),
            'name' => self::siteName(),
            'url' => $homeUrl,
            'description' => loc(Settings::get('seo.default_description')) ?: __('site.meta_description'),
            'founder' => ['@id' => self::id('amir')],
            // The one address the owner gave (config/site.php).
            'address' => self::address(),
            'location' => ['@type' => 'Place', 'address' => self::address()],
            'areaServed' => [
                ['@type' => 'Country', 'name' => 'Netherlands'],
                ['@type' => 'City', 'name' => 'Zwolle'],
                ['@type' => 'City', 'name' => 'Amsterdam'],
            ],
        ];

        if (filled($company['legal_name'] ?? null)) {
            $data['legalName'] = $company['legal_name'];
        }

        if ($email = Site::email()) {
            $data['email'] = $email;
        }

        if ($social = array_column(Site::social(), 'url')) {
            $data['sameAs'] = $social;
        }

        return $data;
    }

    public static function address(): array
    {
        $a = config('site.address');

        return [
            '@type' => 'PostalAddress',
            'streetAddress' => $a['street'],
            'postalCode' => $a['postal_code'],
            'addressLocality' => $a['locality'],
            'addressCountry' => $a['country_code'],
        ];
    }

    public static function website(string $homeUrl): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => self::id('website'),
            'name' => self::siteName(),
            'url' => $homeUrl,
            'publisher' => ['@id' => self::id('org')],
            'inLanguage' => Locale::all(),
        ];
    }

    /**
     * The founder. Facts come from config('site.founder') and the profile links
     * the owner entered in the CMS. No education, by the owner's choice.
     */
    public static function person(): array
    {
        $founder = config('site.founder');
        $sameAs = collect($founder['same_as'] ?? [])->merge(array_column(Site::social(), 'url'))->unique()->values()->all();

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            '@id' => self::id('amir'),
            'name' => $founder['name'],
            'url' => Site::pageUrl('about') ?? Locale::url('home'),
            'jobTitle' => loc($founder['job_title'] ?? null),
            'description' => __('site.founder.bio'),
            'knowsAbout' => $founder['knows_about'] ?? null,
            'worksFor' => ['@id' => self::id('org')],
            'sameAs' => $sameAs ?: null,
        ], fn ($v) => $v !== null && $v !== '' && $v !== []);
    }

    /** The About page describes the founder. */
    public static function profilePage(string $url, string $name): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            '@id' => $url.'#page',
            'url' => $url,
            'name' => $name,
            'inLanguage' => app()->getLocale(),
            'isPartOf' => ['@id' => self::id('website')],
            'mainEntity' => ['@id' => self::id('amir')],
            'about' => ['@id' => self::id('amir')],
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

    /** @param  list<array{question: string, answer: string}>  $items */
    public static function faq(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => collect($items)->map(fn ($item) => [
                '@type' => 'Question', 'name' => $item['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer']],
            ])->values()->all(),
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
            // Every article is written by the founder (owner decision, docs/AI-VISIBILITY.md).
            'author' => ['@type' => 'Person', '@id' => self::id('amir'), 'name' => config('site.founder.name'), 'url' => Site::pageUrl('about') ?? Locale::url('home')],
            'publisher' => ['@type' => 'Organization', '@id' => self::id('org'), 'name' => self::siteName()],
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
            'provider' => ['@type' => 'ProfessionalService', '@id' => self::id('org'), 'name' => self::siteName()],
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
            'creator' => ['@type' => 'Person', '@id' => self::id('amir'), 'name' => config('site.founder.name')],
            'publisher' => ['@type' => 'ProfessionalService', '@id' => self::id('org'), 'name' => self::siteName()],
        ], fn ($v) => $v !== null && $v !== '');
    }
}
