<?php

namespace App\Http\Controllers;

use App\Content\Site;
use App\Models\Page;
use App\Support\Locale;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class SitemapController extends Controller
{
    public function sitemap(): Response
    {
        return response(view('sitemap', ['entries' => $this->entries()])->render(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /** Search and AI crawlers named explicitly, so it is clear they are welcome. */
    private const CRAWLERS = ['*', 'GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot', 'Claude-SearchBot', 'Claude-User', 'PerplexityBot', 'Perplexity-User', 'Google-Extended', 'Applebot-Extended', 'Bingbot'];

    public function robots(): Response
    {
        // One group for every crawler: a bot with its own group would otherwise skip the admin rules.
        $lines = [
            ...array_map(fn ($bot) => 'User-agent: '.$bot, self::CRAWLERS),
            'Allow: /', 'Disallow: /admin', 'Disallow: /preview',
            '', 'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * /llms.txt (llmstxt.org): a short, factual index for language models. Lists
     * only pages that are live; facts come from config('site.founder') and lang.
     */
    public function llms(): Response
    {
        $original = app()->getLocale();
        app()->setLocale('en');

        $line = fn (string $label, ?string $url, string $note = '') => $url ? '- ['.$label.']('.$url.')'.($note !== '' ? ': '.$note : '') : null;
        $live = Page::query()->published()->where('kind', 'system')->get()->keyBy('key');
        $pageUrl = fn (string $key, string $locale) => $live->get($key)?->isTranslatedIn($locale) ? Locale::url($key, $locale) : null;

        $pages = array_filter([
            $line(__('site.llms.about_nl'), $pageUrl('about', 'nl'), __('site.llms.about_note')),
            $line(__('site.llms.about_en'), $pageUrl('about', 'en'), __('site.llms.about_en_note')),
            $line(__('site.llms.work'), $pageUrl('work', 'en'), __('site.llms.work_note')),
            $line(__('site.llms.services'), $pageUrl('services', 'en')),
            $line(__('site.llms.blog'), $pageUrl('blog', 'en')),
            $line(__('site.llms.contact'), $pageUrl('contact', 'en')),
        ]);

        $projects = Site::projects()->filter(fn ($p) => $p->isTranslatedIn('en'))
            ->map(fn ($p) => $line(loc($p->title, 'en'), Locale::route('work.show', ['slug' => $p->slugFor('en')], 'en'), (string) loc($p->kind, 'en')))
            ->filter()->values()->all();

        $related = collect(config('site.founder.same_as', []))->merge(array_column(Site::social(), 'url'))->unique()
            ->map(fn ($url) => $line(preg_replace('#^https?://(www\.)?#', '', rtrim($url, '/')), $url))->values()->all();

        $sections = [
            '# '.config('site.founder.name'),
            '> '.__('site.founder.bio'),
            trans('site.founder.bio', [], 'nl'),
            implode("\n", ['## '.__('site.llms.pages'), ...$pages]),
            $projects ? implode("\n", ['## '.__('site.llms.projects'), ...$projects]) : null,
            $related ? implode("\n", ['## '.__('site.llms.related'), ...$related]) : null,
        ];

        app()->setLocale($original);

        return response(implode("\n\n", array_filter($sections))."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
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
