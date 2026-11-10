<?php

namespace App\Content;

use App\Models\NavigationItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Project;
use App\Models\Service;
use App\Models\Testimonial;
use App\Support\Locale;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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
        return self::findBySlug(Project::class, $slug, $locale, ['cover.media']);
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
