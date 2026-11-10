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
    public static function organization(string $homeUrl): array
    {
        $company = Settings::group('company');
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'ProfessionalService',
            '@id' => $homeUrl.'#organization',
            'name' => Settings::get('seo.site_name') ?: config('site.name'),
            'url' => $homeUrl,
            'description' => loc(Settings::get('seo.default_description')) ?: __('site.meta_description'),
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
            'name' => Settings::get('seo.site_name') ?: config('site.name'),
            'url' => $homeUrl,
            'inLanguage' => app()->getLocale(),
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
            'author' => ['@type' => 'Organization', 'name' => Settings::get('seo.site_name') ?: config('site.name')],
            'publisher' => ['@type' => 'Organization', 'name' => Settings::get('seo.site_name') ?: config('site.name')],
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
            'provider' => ['@type' => 'ProfessionalService', 'name' => Settings::get('seo.site_name') ?: config('site.name')],
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
            'creator' => ['@type' => 'ProfessionalService', 'name' => Settings::get('seo.site_name') ?: config('site.name')],
        ], fn ($v) => $v !== null && $v !== '');
    }
}
