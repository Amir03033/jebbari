<?php

namespace Tests\Feature\Scan;

use App\Scan\Checks\Accessibility;
use App\Scan\Checks\Contrast;
use App\Scan\Checks\Security;
use App\Scan\Checks\Seo;
use App\Scan\Checks\Speed;
use App\Scan\Context;
use App\Scan\Fetched;
use App\Scan\Html;
use Tests\TestCase;

/** The checks against fake pages: no network involved. */
class ChecksTest extends TestCase
{
    private const GOOD_HTML = <<<'HTML'
        <!doctype html><html lang="nl"><head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Bakkerij Jansen · Vers brood in Zwolle sinds 1952</title>
        <meta name="description" content="Bakkerij Jansen bakt elke ochtend vers brood, banket en taarten in hartje Zwolle. Bestel online en haal op in de winkel.">
        <link rel="canonical" href="https://bakkerij.example/">
        <meta property="og:title" content="Bakkerij Jansen"><meta property="og:description" content="Vers brood"><meta property="og:image" content="https://bakkerij.example/og.png">
        <link rel="stylesheet" href="/app.css"><script src="https://bakkerij.example/app.js"></script>
        </head><body><h1>Vers brood</h1><h2>Assortiment</h2><h3>Brood</h3><h2>Contact</h2>
        <img src="/a.jpg" alt="Brood"><img src="/deco.svg" alt="">
        <form><label for="e">E-mail</label><input id="e" type="email"><label>Naam <input type="text"></label><input type="hidden" name="x"><button>Verstuur</button></form>
        </body></html>
        HTML;

    private const BAD_HTML = <<<'HTML'
        <html><head><meta name="robots" content="noindex"><meta name="viewport" content="width=device-width, user-scalable=no">
        <script src="http://cdn.example/old.js"></script></head>
        <body><h1>Een</h1><h1>Twee</h1><h4>Diep</h4><img src="http://cdn.example/a.jpg"><img src="/b.jpg">
        <input type="text" name="q"><select name="s"></select>
        <p style="color: #999; background: #fff">Licht</p></body></html>
        HTML;

    private function context(string $html, array $headers = [], string $url = 'https://bakkerij.example/', array $extra = []): Context
    {
        $page = new Fetched($url, 200, array_change_key_case(array_map(fn ($v) => (array) $v, $headers)), $html, [], 300);

        return new Context(...['page' => $page, 'html' => new Html($html), ...$extra]);
    }

    private function statuses(array $checks): array
    {
        return array_column($checks, 'status', 'key');
    }

    private function variants(array $checks): array
    {
        return array_column($checks, 'variant', 'key');
    }

    public function test_a_well_configured_page_passes_security(): void
    {
        $c = $this->context(self::GOOD_HTML, [
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'Content-Security-Policy' => "default-src 'self'; frame-ancestors 'none'",
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Set-Cookie' => ['session=abc; Path=/; Secure; HttpOnly; SameSite=Lax'],
        ], extra: [
            'httpProbe' => new Fetched('http://bakkerij.example/', 301, ['location' => ['https://bakkerij.example/']], ''),
            'certificate' => ['valid' => true, 'days_left' => 60],
        ]);

        $this->assertSame(array_fill_keys(['https', 'http_redirect', 'certificate', 'hsts', 'csp', 'x_frame_options', 'x_content_type_options', 'referrer_policy', 'mixed_content', 'cookies'], 'good'), $this->statuses(Security::run($c)));
    }

    public function test_a_neglected_page_fails_security(): void
    {
        $c = $this->context(self::BAD_HTML, [
            'Strict-Transport-Security' => 'max-age=600',
            'Content-Security-Policy' => "script-src 'self' 'unsafe-inline'",
            'Set-Cookie' => ['tracker=1; Path=/'],
        ], extra: [
            'httpProbe' => new Fetched('http://bakkerij.example/', 200, [], '<html></html>'),
            'certificate' => ['valid' => true, 'days_left' => 5],
        ]);
        $checks = Security::run($c);
        $s = $this->statuses($checks);
        $v = $this->variants($checks);

        $this->assertSame('bad', $s['http_redirect']);
        $this->assertSame(['warning', 'expiring'], [$s['certificate'], $v['certificate']]);
        $this->assertSame(['warning', 'short'], [$s['hsts'], $v['hsts']]);
        $this->assertSame(['warning', 'weak'], [$s['csp'], $v['csp']]);
        $this->assertSame('warning', $s['x_frame_options']);
        $this->assertSame('warning', $s['x_content_type_options']);
        $this->assertSame('bad', $s['mixed_content'], 'an http:// script is active mixed content');
        $this->assertSame('warning', $s['cookies']);
        $this->assertStringContainsString('tracker (Secure, HttpOnly, SameSite)', collect($checks)->firstWhere('key', 'cookies')['params']['list']);
    }

    public function test_plain_http_and_bad_certificates(): void
    {
        $http = $this->statuses(Security::run($this->context(self::GOOD_HTML, url: 'http://bakkerij.example/')));
        $this->assertSame('bad', $http['https']);
        $this->assertSame('bad', $http['hsts']);
        $this->assertArrayNotHasKey('certificate', $http);
        $this->assertArrayNotHasKey('mixed_content', $http);

        $invalid = Security::run($this->context(self::GOOD_HTML, extra: ['certificate' => ['valid' => false, 'days_left' => 100]]));
        $this->assertSame('invalid', $this->variants($invalid)['certificate']);
        $expired = Security::run($this->context(self::GOOD_HTML, extra: ['certificate' => ['valid' => true, 'days_left' => -3]]));
        $this->assertSame('expired', $this->variants($expired)['certificate']);
    }

    public function test_seo_on_a_good_page(): void
    {
        $robots = new Fetched('https://bakkerij.example/robots.txt', 200, [], "User-agent: *\nDisallow: /admin\nSitemap: https://bakkerij.example/sitemap.xml");
        $s = $this->statuses(Seo::run($this->context(self::GOOD_HTML, extra: ['robots' => $robots, 'sitemap' => true])));

        $this->assertSame(array_fill_keys(['indexable', 'title', 'meta_description', 'h1', 'canonical', 'robots_txt', 'sitemap', 'open_graph', 'alt_seo', 'lang'], 'good'), $s);
    }

    public function test_seo_on_a_bad_page(): void
    {
        $robots = new Fetched('https://bakkerij.example/robots.txt', 200, [], "User-agent: *\nDisallow: /\n");
        $checks = Seo::run($this->context(self::BAD_HTML, extra: ['robots' => $robots]));
        $s = $this->statuses($checks);
        $v = $this->variants($checks);

        $this->assertSame('bad', $s['indexable']);
        $this->assertSame(['bad', 'missing'], [$s['title'], $v['title']]);
        $this->assertSame(['bad', 'missing'], [$s['meta_description'], $v['meta_description']]);
        $this->assertSame(['warning', 'many'], [$s['h1'], $v['h1']]);
        $this->assertSame('warning', $s['canonical']);
        $this->assertSame(['bad', 'blocked'], [$s['robots_txt'], $v['robots_txt']]);
        $this->assertSame('warning', $s['sitemap']);
        $this->assertSame(['warning', 'missing'], [$s['open_graph'], $v['open_graph']]);
        $this->assertSame(['count' => 2, 'total' => 2], collect($checks)->firstWhere('key', 'alt_seo')['params']);
        $this->assertSame('warning', $s['lang']);
    }

    public function test_title_length_and_robots_rules(): void
    {
        $short = $this->variants(Seo::run($this->context('<html><head><title>Home</title></head></html>')));
        $this->assertSame('short', $short['title']);
        $long = $this->variants(Seo::run($this->context('<html><head><title>'.str_repeat('Lang ', 20).'</title></head></html>')));
        $this->assertSame('long', $long['title']);

        $this->assertFalse(Seo::blocksEverything("User-agent: Googlebot\nDisallow: /\n\nUser-agent: *\nDisallow:\n"));
        $this->assertFalse(Seo::blocksEverything("User-agent: *\nDisallow: /\nAllow: /public\n"));
        $this->assertTrue(Seo::blocksEverything("User-agent: Bingbot\nUser-agent: *\nDisallow: / # all\n"));
    }

    public function test_accessibility_on_good_and_bad_pages(): void
    {
        $good = $this->statuses(Accessibility::run($this->context(self::GOOD_HTML)));
        $this->assertSame(['form_labels' => 'good', 'alt' => 'good', 'headings' => 'good', 'viewport' => 'good', 'contrast' => 'skipped'], $good);

        $checks = Accessibility::run($this->context(self::BAD_HTML));
        $s = $this->statuses($checks);
        $v = $this->variants($checks);
        $this->assertSame('bad', $s['form_labels']);
        $this->assertSame(['count' => 2, 'total' => 2], collect($checks)->firstWhere('key', 'form_labels')['params']);
        $this->assertSame('bad', $s['alt']);
        $this->assertSame(['warning', 'skipped'], [$s['headings'], $v['headings']]);
        $this->assertSame(['from' => 'h1', 'to' => 'h4'], collect($checks)->firstWhere('key', 'headings')['params']);
        $this->assertSame(['warning', 'zoom'], [$s['viewport'], $v['viewport']]);
        $this->assertSame(['warning', 'inline_low'], [$s['contrast'], $v['contrast']], '#999 on white is 2.8:1');

        $none = $this->statuses(Accessibility::run($this->context('<html><body><p>x</p></body></html>')));
        $this->assertSame('bad', $none['viewport']);
    }

    public function test_contrast_uses_pagespeed_when_it_ran(): void
    {
        $checks = Accessibility::run($this->context(self::GOOD_HTML, extra: ['pagespeed' => ['contrast' => ['passed' => false, 'count' => 4]]]));
        $this->assertSame(['status' => 'bad', 'params' => ['count' => 4]], array_intersect_key(collect($checks)->firstWhere('key', 'contrast'), ['status' => 1, 'params' => 1]));
    }

    public function test_contrast_ratio(): void
    {
        $this->assertEqualsWithDelta(21.0, Contrast::ratio(Contrast::parse('#000'), Contrast::parse('white')), 0.01);
        $this->assertEqualsWithDelta(4.54, Contrast::ratio(Contrast::parse('rgb(118, 118, 118)'), Contrast::parse('#ffffff')), 0.02);
        $this->assertNull(Contrast::parse('rgba(0,0,0,0.5)'), 'transparent colours are not judged');
    }

    public function test_speed_with_and_without_pagespeed(): void
    {
        $psi = ['performance' => 72, 'lcp' => 3100.0, 'cls' => 0.02, 'tbt' => 750.0, 'bytes' => 2_500_000.0, 'requests' => 40, 'contrast' => null];
        $c = $this->context(self::GOOD_HTML, extra: ['pagespeed' => $psi]);
        $checks = Speed::run($c);

        $this->assertSame(['lcp' => 'warning', 'cls' => 'good', 'tbt' => 'bad', 'response_time' => 'good', 'page_weight' => 'warning', 'requests' => 'good'], $this->statuses($checks));
        $this->assertSame('3,1', $checks[0]['params']['value']);
        $this->assertSame(72, Speed::score($c), 'the PageSpeed score is used as is');

        $c = $this->context(self::GOOD_HTML);
        $checks = Speed::run($c);
        $this->assertSame(['lcp' => 'skipped', 'cls' => 'skipped', 'tbt' => 'skipped', 'response_time' => 'good', 'page_weight' => 'good', 'requests' => 'good'], $this->statuses($checks));
        $this->assertSame(['html', 'estimate'], [$this->variants($checks)['page_weight'], $this->variants($checks)['requests']]);
        $this->assertNull(Speed::score($c), 'no score without PageSpeed: an estimate is not a measurement');
    }
}
