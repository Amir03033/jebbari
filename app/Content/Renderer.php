<?php

namespace App\Content;

use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Project;
use App\Models\Service;
use App\Support\Locale;
use App\Support\Seo;
use App\Support\Settings;
use App\Support\StructuredData;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Builds every public page. The public controllers and the draft preview both
 * call this, so a preview shows exactly the real design with the draft applied.
 * $preview is null on the live site.
 */
class Renderer
{
    private const POSTS_PER_PAGE = 9;

    // ----------------------------------------------------------------- pages

    public static function page(Page $page, ?array $preview = null): View
    {
        $locale = app()->getLocale();
        $key = $page->key;
        $alternates = self::alternates($page, fn (string $l) => $page->kind === 'legal' ? Locale::route('legal', ['slug' => $page->slugFor($l)], $l) : Locale::url($key, $l));
        $canonical = $alternates[$locale] ?? ($page->kind === 'legal' ? Locale::route('legal', ['slug' => $page->slugFor($locale)]) : Locale::url($key));
        $title = loc($page->title);
        $intro = collect($page->blocks ?? [])->firstWhere('type', 'intro');

        $jsonld = $key === 'home'
            ? [StructuredData::organization($canonical), StructuredData::website($canonical)]
            : [StructuredData::breadcrumbs([['name' => __('site.nav.home'), 'url' => Locale::url('home')], ['name' => $title, 'url' => $canonical]])];

        $hero = $key === 'home' ? collect($page->blocks ?? [])->firstWhere('type', 'hero') : null;

        $seo = Seo::make([
            'record' => $page,
            'title' => $title,
            'description' => loc($intro['data']['body'] ?? null) ?: loc($hero['data']['description'] ?? null),
            'raw_title' => $key === 'home',
            'canonical' => $canonical,
            'alternates' => $alternates,
            'jsonld' => $jsonld,
        ]);

        if ($key === 'home' && empty(loc($page->getAttribute('seo')['title'] ?? null))) {
            $seo['title'] = (Settings::get('seo.site_name') ?: config('site.name')).' · '.__('site.descriptor');
        }

        $data = ['page' => $page, 'seo' => $seo, 'preview' => $preview, 'title' => $title];

        return match (true) {
            $key === 'home' => view('pages.home', $data),
            $key === 'work' => view('pages.work', $data + ['projects' => Site::projects()]),
            $key === 'blog' => view('pages.blog', $data + self::blogListing(null)),
            $key === 'contact' => view('pages.contact', $data),
            $page->kind === 'legal' => view('pages.legal', $data),
            default => view('pages.generic', $data),
        };
    }

    // --------------------------------------------------------------- details

    public static function project(Project $project, ?array $preview = null): View
    {
        $locale = app()->getLocale();
        $alternates = self::alternates($project, fn (string $l) => Locale::route('work.show', ['slug' => $project->slugFor($l)], $l));
        $canonical = $alternates[$locale] ?? Locale::route('work.show', ['slug' => $project->slugFor($locale)]);
        $title = loc($project->title);
        $workUrl = Locale::url('work');

        $seo = Seo::make([
            'record' => $project,
            'title' => $title,
            'description' => loc($project->summary) ?: loc($project->brief),
            'canonical' => $canonical,
            'alternates' => $alternates,
            'image' => $project->cover,
            'jsonld' => [
                StructuredData::project($project, $canonical, $project->cover?->shareUrl()),
                StructuredData::breadcrumbs([['name' => __('site.nav.home'), 'url' => Locale::url('home')], ['name' => __('site.nav.work'), 'url' => $workUrl], ['name' => $title, 'url' => $canonical]]),
            ],
        ]);

        $stack = collect($project->stack ?? [])->map(fn ($s) => mb_strtolower($s));
        $related = Site::projects()->reject(fn ($p) => $p->is($project))
            ->sortByDesc(fn ($p) => collect($p->stack ?? [])->map(fn ($s) => mb_strtolower($s))->intersect($stack)->count())
            ->take(2)->values();

        $gallery = collect($project->gallery ?? [])
            ->map(fn ($g) => ['asset' => filled($g['media_id'] ?? null) ? MediaAsset::with('media')->find($g['media_id']) : null, 'caption' => loc($g['caption'] ?? null)])
            ->filter(fn ($g) => $g['asset']?->originalUrl())->values();

        // The next project in the CMS order, for the closing panel. Wraps around; none if it is the only one.
        $all = Site::projects();
        $position = $all->search(fn ($p) => $p->is($project));
        $others = $all->reject(fn ($p) => $p->is($project))->values();
        $next = $others->isEmpty() ? null : ($position === false ? $others->first() : ($all->get($position + 1) ?? $all->first()));

        return view('pages.project', compact('project', 'seo', 'preview', 'related', 'gallery', 'title', 'next'));
    }

    public static function service(Service $service, ?array $preview = null): View
    {
        $locale = app()->getLocale();
        $alternates = self::alternates($service, fn (string $l) => Locale::route('services.show', ['slug' => $service->slugFor($l)], $l));
        $canonical = $alternates[$locale] ?? Locale::route('services.show', ['slug' => $service->slugFor($locale)]);
        $title = loc($service->title);

        $seo = Seo::make([
            'record' => $service,
            'title' => $title,
            'description' => loc($service->summary),
            'canonical' => $canonical,
            'alternates' => $alternates,
            'image' => $service->image,
            'jsonld' => [
                StructuredData::service($service, $canonical),
                StructuredData::breadcrumbs([['name' => __('site.nav.home'), 'url' => Locale::url('home')], ['name' => __('site.nav.services'), 'url' => Locale::url('services')], ['name' => $title, 'url' => $canonical]]),
            ],
        ]);

        $packages = PackagePricing::rows(collect([$service]));
        $others = Site::services()->reject(fn ($s) => $s->is($service))->take(3)->values();

        return view('pages.service', compact('service', 'seo', 'preview', 'packages', 'others', 'title'));
    }

    public static function post(Post $post, ?array $preview = null): View
    {
        $locale = app()->getLocale();
        $alternates = self::alternates($post, fn (string $l) => Locale::route('blog.show', ['slug' => $post->slugFor($l)], $l));
        $canonical = $alternates[$locale] ?? Locale::route('blog.show', ['slug' => $post->slugFor($locale)]);
        $title = loc($post->title);

        $seo = Seo::make([
            'record' => $post,
            'title' => $title,
            'description' => loc($post->excerpt),
            'canonical' => $canonical,
            'alternates' => $alternates,
            'image' => $post->cover,
            'type' => 'article',
            'jsonld' => [
                StructuredData::article($post, $canonical, $post->cover?->shareUrl()),
                StructuredData::breadcrumbs([['name' => __('site.nav.home'), 'url' => Locale::url('home')], ['name' => __('site.nav.blog'), 'url' => Locale::url('blog')], ['name' => $title, 'url' => $canonical]]),
            ],
        ]);

        $all = Site::visible(Site::postsQuery()->get())->reject(fn ($p) => $p->is($post));
        $related = $all->where('category_id', $post->category_id)->take(3);
        $related = $related->count() >= 2 ? $related : $all->take(3);

        $words = str_word_count(strip_tags((string) loc($post->body)));
        $minutes = max(1, (int) ceil($words / 200));

        return view('pages.post', compact('post', 'seo', 'preview', 'related', 'minutes', 'title'));
    }

    public static function category(PostCategory $category): View
    {
        $locale = app()->getLocale();
        $alternates = self::alternates($category, fn (string $l) => Locale::route('blog.category', ['slug' => $category->slugFor($l)], $l));
        $canonical = $alternates[$locale] ?? Locale::route('blog.category', ['slug' => $category->slugFor($locale)]);
        $name = loc($category->name);

        $seo = Seo::make([
            'record' => null,
            'title' => $name.' · '.__('site.nav.blog'),
            'canonical' => $canonical,
            'alternates' => $alternates,
            'jsonld' => [StructuredData::breadcrumbs([['name' => __('site.nav.home'), 'url' => Locale::url('home')], ['name' => __('site.nav.blog'), 'url' => Locale::url('blog')], ['name' => $name, 'url' => $canonical]])],
        ]);

        return view('pages.category', ['category' => $category, 'seo' => $seo, 'preview' => null, 'title' => $name] + self::blogListing($category));
    }

    // --------------------------------------------------------------- helpers

    /** @return array{posts: LengthAwarePaginator, categories: Collection, activeCategory: ?PostCategory} */
    public static function blogListing(?PostCategory $category): array
    {
        $posts = Site::visible(Site::postsQuery()->get());

        if ($category) {
            $posts = $posts->where('category_id', $category->id)->values();
        }

        $page = max(1, (int) request()->query('page', 1));
        $paginator = new LengthAwarePaginator($posts->forPage($page, self::POSTS_PER_PAGE)->values(), $posts->count(), self::POSTS_PER_PAGE, $page, ['path' => request()->url()]);

        return ['posts' => $paginator, 'categories' => Site::categories(), 'activeCategory' => $category];
    }

    /**
     * Where this record exists in each language. A language is only listed when the
     * record is live and complete there, which keeps hreflang and the language switch honest.
     *
     * @return array<string, string>
     */
    private static function alternates(Model $record, \Closure $urlFor): array
    {
        $out = [];

        foreach (Locale::all() as $locale) {
            if ($record->isTranslatedIn($locale) && filled($record->getAttribute('slug_'.$locale) ?? true)) {
                $out[$locale] = $urlFor($locale);
            }
        }

        return $out;
    }
}
