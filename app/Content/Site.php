<?php

namespace App\Content;

use App\Models\NavigationItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Project;
use App\Models\Service;
use App\Models\Testimonial;
use App\Support\ContentCache;
use App\Support\Locale;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Everything the public website reads. The one rule that matters: a visitor
 * only ever sees records that are published (live values, not drafts) and
 * complete in the visitor's language.
 */
class Site
{
    /** Legal pages are addressed by their slug, not a fixed path. */
    public const LEGAL_KEYS = ['privacy', 'terms'];

    // ------------------------------------------------------------ visibility

    /** @param  Collection<int, Model>  $records */
    public static function visible(Collection $records, ?string $locale = null): Collection
    {
        $locale ??= app()->getLocale();

        return $records->filter(fn (Model $r) => $r->isTranslatedIn($locale))->values();
    }

    private static function findBySlug(string $model, string $slug, ?string $locale = null, array $with = []): ?Model
    {
        $locale ??= app()->getLocale();

        /** @var Model|null $record */
        $record = $model::query()->published()->with($with)->where("slug_{$locale}", $slug)->first();

        return $record && $record->isTranslatedIn($locale) ? $record : null;
    }

    // ----------------------------------------------------------------- pages

    public static function page(string $key): ?Page
    {
        $page = Page::query()->published()->where('key', $key)->first();

        return $page && $page->isTranslatedIn(app()->getLocale()) ? $page : null;
    }

    public static function legalPage(string $slug, ?string $locale = null): ?Page
    {
        $locale ??= app()->getLocale();
        $page = Page::query()->published()->where('kind', 'legal')->where("slug_{$locale}", $slug)->first();

        return $page && $page->isTranslatedIn($locale) ? $page : null;
    }

    // ---------------------------------------------------------------- lists

    /** @return Collection<int, Service> */
    public static function services(?array $ids = null): Collection
    {
        $query = Service::query()->published()->with('image.media')->orderBy('sort')->orderBy('id');

        if ($ids !== null) {
            $query->whereIn('id', $ids);
        }

        return self::visible($query->get());
    }

    /** @return Collection<int, Project> */
    /** Technologies used across published projects, each once, in order of first appearance. */
    public static function stack(): Collection
    {
        return self::projects()->flatMap(fn ($project) => $project->stack ?? [])
            ->map(fn ($tech) => trim((string) $tech))->filter()->unique()->values();
    }

    public static function projects(bool $featured = false, ?array $ids = null): Collection
    {
        $query = Project::query()->published()->with('cover.media');

        if ($featured) {
            $query->where('is_featured', true)->orderBy('featured_sort');
        }

        if ($ids !== null) {
            $query->whereIn('id', $ids);
        }

        return self::visible($query->orderBy('sort')->orderByDesc('year')->orderBy('id')->get());
    }

    /** @return Collection<int, Testimonial> */
    public static function testimonials(bool $featured = true, ?array $ids = null): Collection
    {
        $query = Testimonial::query()->published()->where('permission_confirmed', true);

        if ($ids !== null) {
            $query->whereIn('id', $ids);
        } elseif ($featured) {
            $query->where('is_featured', true);
        }

        return self::visible($query->orderBy('sort')->get());
    }

    public static function postsQuery()
    {
        return Post::query()->published()->with(['category', 'cover.media'])->orderByDesc('published_at')->orderByDesc('id');
    }

    /** @return Collection<int, PostCategory> categories that have at least one visible post */
    public static function categories(): Collection
    {
        $used = self::visible(self::postsQuery()->get())->pluck('category_id')->filter()->unique();

        return self::visible(PostCategory::query()->whereIn('id', $used)->orderBy('sort')->get());
    }

    // -------------------------------------------------------------- by slug

    public static function service(string $slug, ?string $locale = null): ?Service
    {
        return self::findBySlug(Service::class, $slug, $locale, ['image.media']);
    }

    public static function project(string $slug, ?string $locale = null): ?Project
    {
        return self::findBySlug(Project::class, $slug, $locale, ['cover.media', 'before.media', 'after.media']);
    }

    public static function post(string $slug, ?string $locale = null): ?Post
    {
        return self::findBySlug(Post::class, $slug, $locale, ['category', 'cover.media']);
    }

    public static function category(string $slug, ?string $locale = null): ?PostCategory
    {
        $locale ??= app()->getLocale();
        $category = PostCategory::query()->where("slug_{$locale}", $slug)->first();

        return $category && $category->isTranslatedIn($locale) ? $category : null;
    }

    // ------------------------------------------------------------------ urls

    /** URL of a system page, only when that page is live in this language. */
    public static function pageUrl(string $key, ?string $locale = null): ?string
    {
        return self::page($key) ? self::url($key, $locale) : null;
    }

    /**
     * A call-to-action from block data: ['label' => ..., 'url' => ...] or null
     * when the label is missing or the target page is not live.
     *
     * @return array{label: string, url: string}|null
     */
    public static function cta(array $data, string $prefix = 'cta'): ?array
    {
        $label = loc($data[$prefix.'_label'] ?? null);
        $url = ($data[$prefix.'_url'] ?? null) ?: self::pageUrl($data[$prefix.'_page'] ?? '');

        return $label && $url ? ['label' => $label, 'url' => $url] : null;
    }

    /** Public URL of a system or legal page key in a locale, or null when it is not available there. */
    public static function url(string $key, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        if (isset(Locale::PAGES[$key])) {
            return Locale::url($key, $locale);
        }

        $slug = Page::query()->published()->where('kind', 'legal')->where('key', $key)->value("slug_{$locale}");

        return $slug ? Locale::route('legal', ['slug' => $slug], $locale) : null;
    }

    /**
     * Navigation for the header or footer. An item appears only when its target
     * page is live in this language (custom links always appear).
     *
     * @return Collection<int, array{label: string, url: string, key: ?string}>
     */
    public static function navigation(string $location): Collection
    {
        $locale = app()->getLocale();

        return NavigationItem::query()->where('location', $location)->where('is_visible', true)->orderBy('sort')->get()
            ->map(function (NavigationItem $item) use ($locale) {
                if ($item->page_key) {
                    $page = Page::query()->published()->where('key', $item->page_key)->first();

                    if (! $page || ! $page->isTranslatedIn($locale)) {
                        return null;
                    }

                    $url = self::url($item->page_key, $locale);
                } else {
                    $url = $item->url;
                }

                $label = loc($item->label) ?: ($item->page_key ? __('site.nav.'.$item->page_key) : null);

                return $url && $label ? ['label' => $label, 'url' => $url, 'key' => $item->page_key] : null;
            })
            ->filter()->values();
    }

    // ---------------------------------------------------------------- search

    /**
     * Everything the command menu can jump to: live pages, projects and articles.
     *
     * @return Collection<int, array{group: string, label: string, hint: ?string, url: string}>
     */
    public static function commandItems(): Collection
    {
        $locale = app()->getLocale();

        // Cached as a plain array: the cache does not restore objects.
        return collect(Cache::remember('command.'.$locale.'.'.ContentCache::version(), 3600, function () use ($locale) {
            $pages = collect(array_keys(Locale::PAGES))
                ->map(fn ($key) => ($page = self::page($key)) ? ['group' => 'pages', 'label' => $key === 'home' ? __('site.nav.home') : loc($page->title), 'hint' => null, 'url' => Locale::url($key)] : null)
                ->filter();

            $projects = self::projects()->map(fn ($p) => ['group' => 'work', 'label' => loc($p->title), 'hint' => loc($p->kind), 'url' => Locale::route('work.show', ['slug' => $p->slugFor($locale)])]);
            $services = self::services()->map(fn ($s) => ['group' => 'services', 'label' => loc($s->title), 'hint' => null, 'url' => Locale::route('services.show', ['slug' => $s->slugFor($locale)])]);
            $posts = self::visible(self::postsQuery()->limit(20)->get())->map(fn ($p) => ['group' => 'blog', 'label' => loc($p->title), 'hint' => $p->category ? loc($p->category->name) : null, 'url' => Locale::route('blog.show', ['slug' => $p->slugFor($locale)])]);

            return $pages->concat($projects)->concat($services)->concat($posts)->values()->all();
        }));
    }

    // ---------------------------------------------------------------- github

    /**
     * Public repositories of the GitHub account set in the CMS, most recently
     * pushed first. Fetched by the server and cached, so a visitor's browser
     * never talks to GitHub. Empty when no account is set or GitHub is unreachable.
     *
     * @return Collection<int, array{name: string, description: ?string, language: ?string, stars: int, url: string, pushed_at: ?string}>
     */
    public static function github(): Collection
    {
        $user = trim((string) self::setting('social.github_username'));

        if ($user === '' || ! preg_match('/^[A-Za-z0-9-]{1,39}$/', $user)) {
            return collect();
        }

        $key = 'github.'.strtolower($user);

        if (Cache::has($key)) {
            return collect(Cache::get($key));
        }

        try {
            $response = Http::timeout(4)->acceptJson()->withUserAgent('website')->get("https://api.github.com/users/{$user}/repos", ['sort' => 'pushed', 'per_page' => 30, 'type' => 'owner']);
            $repos = $response->successful() ? collect($response->json())
                ->reject(fn ($r) => ($r['fork'] ?? false) || ($r['archived'] ?? false) || ($r['private'] ?? false))
                ->take(6)
                ->map(fn ($r) => [
                    'name' => (string) $r['name'], 'description' => $r['description'] ?? null, 'language' => $r['language'] ?? null,
                    'stars' => (int) ($r['stargazers_count'] ?? 0), 'url' => (string) $r['html_url'], 'pushed_at' => $r['pushed_at'] ?? null,
                ])->values()->all() : null;
        } catch (Throwable $e) {
            report($e);
            $repos = null;
        }

        // A failed lookup is remembered briefly, so a GitHub outage never slows the site down.
        Cache::put($key, $repos ?? [], $repos === null ? 900 : 21600);

        return collect($repos ?? []);
    }

    /** Hosts of live demos on published projects: the only sites the pages may embed. */
    public static function demoHosts(): array
    {
        return Cache::remember('demo.hosts.'.ContentCache::version(), 86400, fn () => Project::query()->published()->whereNotNull('demo_url')->pluck('demo_url')
            ->map(fn ($url) => parse_url((string) $url, PHP_URL_SCHEME) === 'https' ? parse_url((string) $url, PHP_URL_HOST) : null)
            ->filter()->unique()->values()->all());
    }

    // -------------------------------------------------------------- settings

    public static function setting(string $path, mixed $default = null): mixed
    {
        return Settings::get($path, $default);
    }

    /** The contact email, from the CMS. Null until the owner has entered one. */
    public static function email(): ?string
    {
        return self::setting('contact.email') ?: null;
    }

    /** @return list<array{label: string, url: string}> */
    public static function social(): array
    {
        return collect(self::setting('social.links', []))->filter(fn ($l) => filled($l['label'] ?? null) && filled($l['url'] ?? null))->values()->all();
    }

    public static function takingProjects(): bool
    {
        return (bool) self::setting('header.taking_projects', true);
    }

    public static function statusText(): string
    {
        $text = loc(self::setting('header.'.(self::takingProjects() ? 'status_open' : 'status_closed')));

        return $text ?: __('site.status.'.(self::takingProjects() ? 'open' : 'closed'));
    }
}
