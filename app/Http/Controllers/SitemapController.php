<?php

namespace App\Http\Controllers;

use App\Content\Site;
use App\Models\Page;
use App\Support\Locale;
use App\Support\Settings;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class SitemapController extends Controller
{
    public function sitemap(): Response
    {
        return response(view('sitemap', ['entries' => $this->entries()])->render(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * Crawlers that read the site for AI answers and search. They are named so it is
     * explicit that they are welcome. A named group replaces the "*" group for that
     * crawler, so every group repeats the same rules.
     */
    public const CRAWLERS = [
        '*', 'Googlebot', 'Bingbot', 'Google-Extended', 'Applebot-Extended',
        'GPTBot', 'OAI-SearchBot', 'ChatGPT-User',
        'ClaudeBot', 'Claude-SearchBot', 'Claude-User',
        'PerplexityBot', 'Perplexity-User',
    ];

    public function robots(): Response
    {
        $lines = [];

        foreach (self::CRAWLERS as $agent) {
            array_push($lines, 'User-agent: '.$agent, 'Allow: /', 'Disallow: /admin', 'Disallow: /preview', '');
        }

        $lines[] = 'Sitemap: '.url('/sitemap.xml');

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /** /llms.txt: a short, factual summary for language models (llmstxt.org). Only live pages are listed. */
    public function llms(): Response
    {
        $original = app()->getLocale();
        $person = config('site.person');
        $name = Settings::get('seo.site_name') ?: config('site.name');

        $pages = [];
        foreach (['about', 'work', 'services', 'blog', 'contact'] as $key) {
            foreach (Locale::all() as $locale) {
                app()->setLocale($locale);
                if ($page = Site::page($key)) {
                    $pages[] = ['title' => loc($page->title).' ('.strtoupper($locale).')', 'url' => Locale::url($key, $locale), 'note' => $key === 'about' ? $person['description'][$locale] : null];
                }
            }
        }

        app()->setLocale('en');
        $projects = Site::projects()->map(fn ($p) => [
            'title' => loc($p->title),
            'url' => Locale::route('work.show', ['slug' => $p->slugFor('en')], 'en'),
            'note' => loc($p->summary) ?: loc($p->kind),
        ])->all();
        $posts = Site::visible(Site::postsQuery()->get())->take(20)->map(fn ($p) => [
            'title' => loc($p->title),
            'url' => Locale::route('blog.show', ['slug' => $p->slugFor('en')], 'en'),
            'note' => loc($p->excerpt),
        ])->all();
        app()->setLocale($original);

        $section = function (string $heading, array $items): array {
            if ($items === []) {
                return [];
            }

            $out = ['', '## '.$heading, ''];
            foreach ($items as $item) {
                $out[] = '- ['.$item['title'].']('.$item['url'].')'.(filled($item['note']) ? ': '.trim(preg_replace('/\s+/', ' ', strip_tags($item['note']))) : '');
            }

            return $out;
        };

        $related = collect($person['related_sites'])->map(fn ($note, $url) => ['title' => parse_url($url, PHP_URL_HOST), 'url' => $url, 'note' => $note])->values()->all();

        $lines = [
            '# '.$person['name'],
            '',
            '> '.$person['description']['en'],
            '',
            $person['name'].' founded '.$name.' and built it himself. '.$name.' designs and builds web applications and websites, working from Amsterdam and Zwolle, the Netherlands. Stack: Laravel (PHP), Vue 3, TypeScript, Filament, Blade, Tailwind. Fields: web applications, SaaS, server and web application security.',
            ...$section('Pages', $pages),
            ...$section('Projects', $projects),
            ...$section('Articles', $posts),
            ...$section('Related sites', $related),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * One entry per page, listing the URL in every language where it is live and
     * complete. Hidden from search (noindex) means not listed.
     *
     * @return Collection<int, array{urls: array<string, string>, lastmod: ?string}>
     */
    private function entries(): Collection
    {
        $entries = collect();

        $add = function ($record, \Closure $urlFor) use ($entries) {
            if (($record->getAttribute('seo')['noindex'] ?? false) === true) {
                return;
            }

            $urls = [];
            foreach (Locale::all() as $locale) {
                if ($record->isTranslatedIn($locale)) {
                    app()->setLocale($locale);
                    $urls[$locale] = $urlFor($record, $locale);
                }
            }

            if ($urls) {
                $entries->push(['urls' => $urls, 'lastmod' => $record->updated_at?->toAtomString()]);
            }
        };

        $original = app()->getLocale();

        Page::query()->published()->get()->each(fn ($page) => $add($page, fn ($p, $l) => $p->kind === 'legal'
            ? Locale::route('legal', ['slug' => $p->slugFor($l)], $l)
            : Locale::url($p->key, $l)));

        Site::projects()->each(fn ($r) => $add($r, fn ($p, $l) => Locale::route('work.show', ['slug' => $p->slugFor($l)], $l)));
        Site::services()->each(fn ($r) => $add($r, fn ($p, $l) => Locale::route('services.show', ['slug' => $p->slugFor($l)], $l)));
        Site::visible(Site::postsQuery()->get())->each(fn ($r) => $add($r, fn ($p, $l) => Locale::route('blog.show', ['slug' => $p->slugFor($l)], $l)));
        Site::categories()->each(fn ($r) => $add($r, fn ($p, $l) => Locale::route('blog.category', ['slug' => $p->slugFor($l)], $l)));

        app()->setLocale($original);

        return $entries;
    }
}
