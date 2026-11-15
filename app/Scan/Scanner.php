<?php

namespace App\Scan;

use App\Scan\Checks\Accessibility;
use App\Scan\Checks\Security;
use App\Scan\Checks\Seo;
use App\Scan\Checks\Speed;
use Throwable;

/**
 * Runs one scan of one page: fetch it (through the SSRF guard), gather the
 * extras (http:// probe, certificate, robots.txt, sitemap, PageSpeed) and run
 * the four groups of checks. Only the given page is checked, not the site.
 */
class Scanner
{
    public function __construct(
        private Fetcher $fetcher,
        private UrlGuard $guard,
        private CertificateInspector $certificates,
        private PageSpeed $pagespeed,
    ) {}

    /**
     * @return array{url: string, scores: array<string, ?int>, results: array<string, list<array>>}
     *
     * @throws UnsafeUrl|FetchFailed
     */
    public function scan(string $url): array
    {
        $page = $this->fetcher->get($url);

        if ($page->status >= 400) {
            throw new FetchFailed('status');
        }

        if (! preg_match('#(text/html|application/xhtml)#i', (string) $page->header('content-type')) && ! preg_match('/<html|<!doctype html/i', substr($page->body, 0, 2048))) {
            throw new FetchFailed('not_html');
        }

        $final = $this->guard->check($page->url);
        $origin = Url::origin($page->url);
        $robots = $this->optional(fn () => $this->fetcher->get($origin.'/robots.txt', 2, 256 * 1024));

        $context = new Context(
            page: $page,
            html: new Html($page->body),
            httpProbe: $this->optional(fn () => $this->fetcher->get('http://'.$final['host'].(parse_url($page->url, PHP_URL_PATH) ?: '/'), 0, 64 * 1024)),
            certificate: $page->isHttps() ? $this->optional(fn () => $this->certificates->inspect($final['host'], $final['ip'])) : null,
            robots: $robots,
            sitemap: $this->hasSitemap($origin, $robots),
            pagespeed: $this->optional(fn () => $this->pagespeed->run($page->url)),
        );

        $results = [
            'security' => Security::run($context),
            'seo' => Seo::run($context),
            'speed' => Speed::run($context),
            'accessibility' => Accessibility::run($context),
        ];

        return [
            'url' => $page->url,
            'scores' => [
                'security' => Check::score($results['security']),
                'seo' => Check::score($results['seo']),
                'speed' => Speed::score($context),
                'accessibility' => Check::score($results['accessibility']),
            ],
            'results' => $results,
        ];
    }

    private function hasSitemap(string $origin, ?Fetched $robots): bool
    {
        $candidates = [];

        if ($robots?->ok() && preg_match_all('/^\s*sitemap\s*:\s*(\S+)/im', $robots->body, $m)) {
            $candidates = array_slice($m[1], 0, 2);
        }

        $candidates[] = $origin.'/sitemap.xml';

        foreach (array_unique($candidates) as $candidate) {
            $sitemap = $this->optional(fn () => $this->fetcher->get($candidate, 2, 512 * 1024));

            if ($sitemap?->ok() && preg_match('/<(urlset|sitemapindex)\b/i', $sitemap->body)) {
                return true;
            }
        }

        return false;
    }

    /** Extras may fail (or be refused by the guard) without failing the scan. */
    private function optional(callable $fetch): mixed
    {
        try {
            return $fetch();
        } catch (Throwable) {
            return null;
        }
    }
}
