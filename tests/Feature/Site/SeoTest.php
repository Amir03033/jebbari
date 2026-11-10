<?php

namespace Tests\Feature\Site;

use App\Models\Page;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsSiteContent;
use Tests\Support\PublishesSite;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use BuildsSiteContent, PublishesSite, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publishSite();
    }

    private function jsonLd(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);

        return array_map(fn ($json) => json_decode(html_entity_decode($json), true, 512, JSON_THROW_ON_ERROR), $m[1]);
    }

    private function meta(string $html, string $name, string $attr = 'name'): ?string
    {
        return preg_match('#<meta '.$attr.'="'.preg_quote($name, '#').'" content="([^"]*)"#', $html, $m) ? html_entity_decode($m[1]) : null;
    }

    public function test_every_page_has_a_unique_title_and_description_per_language(): void
    {
        $service = $this->makeService();
        $project = $this->makeProject();
        $post = $this->makePost();
        $urls = ['/nl', '/nl/werk', '/nl/diensten', '/nl/over-ons', '/nl/blog', '/nl/contact', "/nl/diensten/{$service->slug_nl}", "/nl/werk/{$project->slug_nl}", "/nl/blog/{$post->slug_nl}"];

        $titles = [];
        foreach ($urls as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            preg_match('#<title>([^<]+)</title>#', $html, $t);
            $titles[$url] = html_entity_decode($t[1]);
            $this->assertNotEmpty($this->meta($html, 'description'), "$url needs a meta description");
        }

        $this->assertSame(count($urls), count(array_unique($titles)), 'titles are unique: '.json_encode($titles));
        $this->assertSame('Jebbari · Softwareontwikkelaar & ondernemer', $titles['/nl']);
        $this->assertSame('Jebbari · Software developer &amp; entrepreneur', preg_replace('#.*<title>([^<]+)</title>.*#s', '$1', $this->get('/en')->getContent()));
    }

    public function test_titles_follow_the_cms_template_and_description_defaults(): void
    {
        $service = $this->makeService();

        $html = $this->get("/nl/diensten/{$service->slug_nl}")->getContent();
        $this->assertStringContainsString('<title>Webbouw · Jebbari</title>', $html);
        $this->assertSame('Wij bouwen webbouw.', $this->meta($html, 'description'), 'falls back to the summary');

        Settings::save('seo', ['site_name' => 'Jebbari Studio', 'title_template' => '{title} | {site}'.' ', 'default_description' => ['nl' => 'Standaardbeschrijving.', 'en' => 'Default description.']]);
        Settings::save('seo', ['site_name' => 'Jebbari Studio', 'title_template' => '{title} | Studio', 'default_description' => ['nl' => 'Standaardbeschrijving.', 'en' => 'Default description.']]);

        $this->assertStringContainsString('<title>Webbouw | Studio</title>', $this->get("/nl/diensten/{$service->slug_nl}")->getContent());
        $this->assertSame('Standaardbeschrijving.', $this->meta($this->get('/nl/contact')->getContent(), 'description'));
    }

    public function test_cms_seo_fields_override_the_defaults_per_language(): void
    {
        $service = $this->makeService(['seo' => ['title' => ['nl' => 'Eigen titel', 'en' => 'Own title'], 'description' => ['nl' => 'Eigen beschrijving.', 'en' => 'Own description.']]]);

        $nl = $this->get("/nl/diensten/{$service->slug_nl}")->getContent();
        $this->assertStringContainsString('<title>Eigen titel</title>', $nl);
        $this->assertSame('Eigen beschrijving.', $this->meta($nl, 'description'));
        $this->assertStringContainsString('<title>Own title</title>', $this->get("/en/services/{$service->slug_en}")->getContent());
    }

    public function test_canonical_and_hreflang_tags_are_correct_for_pages_and_details(): void
    {
        $service = $this->makeService();

        $html = $this->get("/nl/diensten/{$service->slug_nl}")->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="'.url("/nl/diensten/{$service->slug_nl}").'">', $html);
        $this->assertStringContainsString('<link rel="alternate" hreflang="nl" href="'.url("/nl/diensten/{$service->slug_nl}").'">', $html);
        $this->assertStringContainsString('<link rel="alternate" hreflang="en" href="'.url("/en/services/{$service->slug_en}").'">', $html);
        $this->assertStringContainsString('<link rel="alternate" hreflang="x-default" href="'.url("/nl/diensten/{$service->slug_nl}").'">', $html);

        $en = $this->get('/en')->getContent();
        $this->assertStringContainsString('<link rel="canonical" href="'.url('/en').'">', $en);
        $this->assertStringContainsString('hreflang="nl" href="'.url('/nl').'"', $en);
    }

    public function test_hreflang_only_lists_languages_where_the_page_exists(): void
    {
        $project = $this->makeProject();
        $project->forceFill(['title' => ['nl' => 'Testproject', 'en' => '']])->saveQuietly();

        $html = $this->get("/nl/werk/{$project->slug_nl}")->getContent();

        $this->assertStringContainsString('<link rel="alternate" hreflang="nl"', $html);
        $this->assertStringNotContainsString('<link rel="alternate" hreflang="en"', $html);
        $this->assertStringNotContainsString('<link rel="alternate" hreflang="x-default" href="'.url('/en'), $html);
    }

    public function test_open_graph_and_twitter_tags_are_present(): void
    {
        $service = $this->makeService();
        $html = $this->get("/nl/diensten/{$service->slug_nl}")->getContent();

        $this->assertSame('Webbouw · Jebbari', $this->meta($html, 'og:title', 'property'));
        $this->assertSame('website', $this->meta($html, 'og:type', 'property'));
        $this->assertSame(url("/nl/diensten/{$service->slug_nl}"), $this->meta($html, 'og:url', 'property'));
        $this->assertSame('nl_NL', $this->meta($html, 'og:locale', 'property'));
        $this->assertSame('en_GB', $this->meta($html, 'og:locale:alternate', 'property'));
        $this->assertSame('Jebbari', $this->meta($html, 'og:site_name', 'property'));
        $this->assertSame(asset('og/default.png'), $this->meta($html, 'og:image', 'property'));
        $this->assertSame('summary_large_image', $this->meta($html, 'twitter:card'));
        $this->assertSame(asset('og/default.png'), $this->meta($html, 'twitter:image'));
    }

    public function test_the_default_share_image_exists_and_is_a_valid_png(): void
    {
        $path = public_path('og/default.png');

        $this->assertFileExists($path);
        $this->assertSame([1200, 630], array_slice(getimagesize($path), 0, 2));
        $this->assertSame('image/png', getimagesize($path)['mime']);
    }

    public function test_a_project_cover_becomes_its_share_image_with_alt_text(): void
    {
        $asset = $this->makeImage('Cover', alt: ['nl' => 'Een cover', 'en' => 'A cover']);
        $project = $this->makeProject(['cover_media_id' => $asset->id]);

        $html = $this->get("/nl/werk/{$project->slug_nl}")->getContent();

        $this->assertSame($asset->shareUrl(), $this->meta($html, 'og:image', 'property'));
        $this->assertStringContainsString('webp-1200', $this->meta($html, 'og:image', 'property'));
        $this->assertSame('Een cover', $this->meta($html, 'og:image:alt', 'property'));
    }

    public function test_noindex_from_the_cms_removes_canonical_and_social_tags_and_the_sitemap_entry(): void
    {
        $service = $this->makeService(['seo' => ['noindex' => true]]);

        $html = $this->get("/nl/diensten/{$service->slug_nl}")->getContent();

        $this->assertSame('noindex, nofollow', $this->meta($html, 'robots'));
        $this->assertStringNotContainsString('rel="canonical"', $html);
        $this->assertStringNotContainsString('og:title', $html);
        $this->assertStringNotContainsString($service->slug_nl, $this->get('/sitemap.xml')->getContent());
    }

    public function test_indexable_pages_have_no_robots_meta(): void
    {
        $this->assertNull($this->meta($this->get('/nl')->getContent(), 'robots'));
    }

    // ----------------------------------------------------------- structured data

    public function test_the_homepage_has_organization_and_website_data_without_invented_facts(): void
    {
        $data = collect($this->jsonLd($this->get('/nl')->getContent()));

        $org = $data->firstWhere('@type', 'ProfessionalService');
        $this->assertSame('Jebbari', $org['name']);
        $this->assertSame(url('/nl'), $org['url']);
        $this->assertSame(['Netherlands', 'Zwolle', 'Amsterdam'], array_column($org['areaServed'], 'name'));
        foreach (['address', 'telephone', 'email', 'sameAs', 'aggregateRating', 'review', 'foundingDate', 'numberOfEmployees'] as $key) {
            $this->assertArrayNotHasKey($key, $org, "$key must not appear until the owner provides it");
        }
        $this->assertSame('WebSite', $data->firstWhere('@type', 'WebSite')['@type']);
    }

    public function test_organization_data_includes_only_company_details_the_owner_entered(): void
    {
        Settings::save('company', ['legal_name' => 'Jebbari B.V.', 'btw' => 'NL123456789B01']);
        Settings::save('contact', ['email' => 'studio@voorbeeld.test']);
        Settings::save('social', ['links' => [['label' => 'LinkedIn', 'url' => 'https://linkedin.example.test/x']]]);

        $org = collect($this->jsonLd($this->get('/nl')->getContent()))->firstWhere('@type', 'ProfessionalService');

        $this->assertSame('Jebbari B.V.', $org['legalName']);
        $this->assertSame('NL123456789B01', $org['vatID']);
        $this->assertSame('studio@voorbeeld.test', $org['email']);
        $this->assertSame(['https://linkedin.example.test/x'], $org['sameAs']);
    }

    public function test_detail_pages_have_typed_structured_data_and_breadcrumbs(): void
    {
        $asset = $this->makeImage();
        $service = $this->makeService();
        $project = $this->makeProject(['stack' => ['Laravel'], 'year' => 2026, 'cover_media_id' => $asset->id]);
        $post = $this->makePost(['cover_media_id' => $asset->id]);

        $s = collect($this->jsonLd($this->get("/nl/diensten/{$service->slug_nl}")->getContent()));
        $this->assertSame('Webbouw', $s->firstWhere('@type', 'Service')['name']);
        $this->assertSame(['Home', 'Diensten', 'Webbouw'], array_column($s->firstWhere('@type', 'BreadcrumbList')['itemListElement'], 'name'));

        $p = collect($this->jsonLd($this->get("/en/work/{$project->slug_en}")->getContent()));
        $work = $p->firstWhere('@type', 'CreativeWork');
        $this->assertSame('Test project', $work['name']);
        $this->assertSame('Laravel', $work['keywords']);
        $this->assertStringStartsWith('http', $work['image']);
        $this->assertSame(['Home', 'Work', 'Test project'], array_column($p->firstWhere('@type', 'BreadcrumbList')['itemListElement'], 'name'));

        $a = collect($this->jsonLd($this->get("/nl/blog/{$post->slug_nl}")->getContent()))->firstWhere('@type', 'BlogPosting');
        $this->assertSame('Een artikel', $a['headline']);
        $this->assertSame('nl', $a['inLanguage']);
        $this->assertSame($post->published_at->toIso8601String(), $a['datePublished']);
        $this->assertStringStartsWith('http', $a['image']);
    }

    public function test_structured_data_is_safe_to_embed(): void
    {
        $service = $this->makeService(['title' => ['nl' => 'A </script><b>x</b>', 'en' => 'B']]);

        $html = $this->get("/nl/diensten/{$service->slug_nl}")->getContent();

        $this->assertSame(1, preg_match_all('#</script>#', substr($html, strpos($html, 'application/ld+json')), $m) ? 1 : 0);
        $this->assertStringNotContainsString('A </script>', $html);
        $this->jsonLd($html);
    }

    // ------------------------------------------------------------------ sitemap

    public function test_the_sitemap_lists_only_live_complete_urls_with_language_alternates(): void
    {
        $service = $this->makeService();
        $draft = $this->makeService(['title' => ['nl' => 'Geheimconcept', 'en' => 'Secret draft']], publish: false);
        $project = $this->makeProject();
        $post = $this->makePost();

        $response = $this->get('/sitemap.xml')->assertOk();
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
        $xml = $response->getContent();

        $doc = simplexml_load_string($xml);
        $this->assertNotFalse($doc, 'valid XML');

        foreach ([url('/nl'), url('/en'), url('/nl/werk'), url('/en/work'), url("/nl/diensten/{$service->slug_nl}"), url("/en/services/{$service->slug_en}"), url("/nl/werk/{$project->slug_nl}"), url("/nl/blog/{$post->slug_nl}")] as $loc) {
            $this->assertStringContainsString("<loc>$loc</loc>", $xml);
        }

        $this->assertStringNotContainsString('Geheimconcept', $xml);
        $this->assertStringNotContainsString($draft->slug_nl, $xml);
        $this->assertStringContainsString('<xhtml:link rel="alternate" hreflang="en" href="'.url("/en/services/{$service->slug_en}").'"/>', $xml);
        $this->assertStringContainsString('<xhtml:link rel="alternate" hreflang="x-default" href="'.url('/nl').'"/>', $xml);
        $this->assertStringContainsString('<lastmod>', $xml);
        $this->assertStringNotContainsString('/admin', $xml);
        $this->assertStringNotContainsString('/preview', $xml);
    }

    public function test_unpublished_system_pages_and_legal_drafts_are_not_in_the_sitemap(): void
    {
        Page::where('key', 'blog')->first()->unpublish();

        $xml = $this->get('/sitemap.xml')->getContent();

        $this->assertStringNotContainsString(url('/nl/blog'), $xml);
        $this->assertStringNotContainsString('/privacy', $xml);
    }

    public function test_robots_txt_points_to_the_sitemap(): void
    {
        $robots = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString('User-agent: *', $robots);
        $this->assertStringContainsString('Sitemap: '.url('/sitemap.xml'), $robots);
        $this->assertStringNotContainsString('Disallow: /'."\n", $robots, 'the whole site is not blocked');
    }

    // ----------------------------------------------- strict CSP and no third parties

    public function test_public_pages_comply_with_the_strict_csp_and_load_nothing_from_third_parties(): void
    {
        $service = $this->makeService();
        $project = $this->makeProject();
        $post = $this->makePost();

        foreach (['/nl', '/en', '/nl/werk', '/nl/diensten', '/nl/over-ons', '/nl/blog', '/nl/contact', "/nl/diensten/{$service->slug_nl}", "/nl/werk/{$project->slug_nl}", "/nl/blog/{$post->slug_nl}"] as $url) {
            $response = $this->get($url)->assertOk();
            $html = $response->getContent();
            $csp = $response->headers->get('Content-Security-Policy');

            $this->assertStringContainsString("script-src 'self'", $csp);
            $this->assertStringNotContainsString('unsafe', $csp);
            $this->assertSame(0, preg_match('/<[a-z][^>]*\sstyle="/i', $html), "$url has inline style attributes");
            $this->assertSame(0, preg_match('/<[a-z][^>]*\son[a-z]+="/i', $html), "$url has inline event handlers");
            $this->assertSame(0, preg_match('#<script(?![^>]*\bsrc=)(?![^>]*application/ld\+json)#i', $html), "$url has an inline script");
            $this->assertSame(0, preg_match('#<style\b#i', $html), "$url has an inline style block");

            preg_match_all('#(?:src|href|action)="(https?://[^"]+)"#', $html, $links);
            foreach ($links[1] as $link) {
                $host = parse_url($link, PHP_URL_HOST);
                $this->assertContains($host, ['localhost', 'hello', null], "$url references a third-party host: $link");
            }
        }
    }
}
