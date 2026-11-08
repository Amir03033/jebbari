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

    public function robots(): Response
    {
        $lines = ['User-agent: *', 'Disallow: /admin', 'Disallow: /preview', '', 'Sitemap: '.url('/sitemap.xml')];

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
