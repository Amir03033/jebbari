<?php

namespace App\Scan\Checks;

use App\Scan\Check;
use App\Scan\Context;

class Seo
{
    public static function run(Context $c): array
    {
        return [
            self::indexable($c),
            self::title($c),
            self::description($c),
            self::h1($c),
            Check::make('canonical', filled($c->html->link('canonical')) ? Check::GOOD : Check::WARNING),
            self::robots($c),
            Check::make('sitemap', $c->sitemap ? Check::GOOD : Check::WARNING),
            self::openGraph($c),
            self::alt($c, 'alt_seo', Check::WARNING),
            Check::make('lang', filled($c->html->first('//html')?->getAttribute('lang')) ? Check::GOOD : Check::WARNING),
        ];
    }

    private static function indexable(Context $c): array
    {
        $robots = strtolower(($c->html->meta('robots') ?? '').' '.implode(' ', $c->page->headerAll('x-robots-tag')));

        return str_contains($robots, 'noindex') ? Check::make('indexable', Check::BAD) : Check::make('indexable', Check::GOOD);
    }

    private static function title(Context $c): array
    {
        $title = $c->html->text('//head/title') ?? $c->html->text('//title');
        $length = mb_strlen((string) $title);

        return match (true) {
            $length === 0 => Check::make('title', Check::BAD, 'missing'),
            $length < 30 => Check::make('title', Check::WARNING, 'short', ['length' => $length]),
            $length > 60 => Check::make('title', Check::WARNING, 'long', ['length' => $length]),
            default => Check::make('title', Check::GOOD, null, ['length' => $length]),
        };
    }

    private static function description(Context $c): array
    {
        $length = mb_strlen((string) $c->html->meta('description'));

        return match (true) {
            $length === 0 => Check::make('meta_description', Check::BAD, 'missing'),
            $length < 70 => Check::make('meta_description', Check::WARNING, 'short', ['length' => $length]),
            $length > 160 => Check::make('meta_description', Check::WARNING, 'long', ['length' => $length]),
            default => Check::make('meta_description', Check::GOOD, null, ['length' => $length]),
        };
    }

    private static function h1(Context $c): array
    {
        $count = count($c->html->all('//h1'));

        return match (true) {
            $count === 0 => Check::make('h1', Check::BAD, 'missing'),
            $count > 1 => Check::make('h1', Check::WARNING, 'many', ['count' => $count]),
            default => Check::make('h1', Check::GOOD),
        };
    }

    private static function robots(Context $c): array
    {
        if ($c->robots === null || ! $c->robots->ok()) {
            return Check::make('robots_txt', Check::WARNING, 'missing');
        }

        return self::blocksEverything($c->robots->body)
            ? Check::make('robots_txt', Check::BAD, 'blocked')
            : Check::make('robots_txt', Check::GOOD);
    }

    /** "User-agent: *" followed by "Disallow: /" (and no Allow) shuts every search engine out. */
    public static function blocksEverything(string $robots): bool
    {
        $agents = [];
        $inGroup = false;
        $blocks = false;

        foreach (preg_split('/\R/', $robots) as $line) {
            $line = trim(preg_replace('/#.*$/', '', $line));

            if (! str_contains($line, ':')) {
                continue;
            }

            [$field, $value] = array_map('trim', explode(':', $line, 2));
            $field = strtolower($field);

            if ($field === 'user-agent') {
                $agents = $inGroup ? [$value] : [...$agents, $value];
                $inGroup = false;

                continue;
            }

            $inGroup = true;

            if (in_array('*', $agents, true)) {
                if ($field === 'disallow' && $value === '/') {
                    $blocks = true;
                }

                if ($field === 'allow' && $value !== '') {
                    return false;
                }
            }
        }

        return $blocks;
    }

    private static function openGraph(Context $c): array
    {
        $present = array_filter(['og:title', 'og:description', 'og:image'], fn ($p) => filled($c->html->meta($p)));

        return match (count($present)) {
            3 => Check::make('open_graph', Check::GOOD),
            0 => Check::make('open_graph', Check::WARNING, 'missing'),
            default => Check::make('open_graph', Check::WARNING, 'partial'),
        };
    }

    /** Images without an alt attribute. alt="" is fine: it marks an image as decoration. */
    public static function alt(Context $c, string $key, string $failStatus): array
    {
        $images = $c->html->all('//img');
        $missing = count(array_filter($images, fn ($img) => ! $img->hasAttribute('alt')));

        return match (true) {
            $images === [] => Check::make($key, Check::GOOD, 'none'),
            $missing === 0 => Check::make($key, Check::GOOD, null, ['total' => count($images)]),
            default => Check::make($key, $failStatus, null, ['count' => $missing, 'total' => count($images)]),
        };
    }
}
