<?php

namespace Tests\Feature\Site;

use App\Models\NavigationItem;
use App\Models\Page;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsSiteContent;
use Tests\Support\PublishesSite;
use Tests\TestCase;

/** The About page, founder data, robots.txt and llms.txt that tell search engines and AI who Amir Jebbari is. */
class AiVisibilityTest extends TestCase
{
    use BuildsSiteContent, PublishesSite, RefreshDatabase;

    private const BIO_NL = 'Amir Jebbari is de oprichter van Jebbari, gevestigd in Amsterdam en Zwolle.';

    private const BIO_EN = 'Amir Jebbari is the founder of Jebbari, based in Amsterdam and Zwolle, the Netherlands.';

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

    private function draftAndPublishAbout(): void
    {
        $this->artisan('cms:draft-about')->assertSuccessful();
        Page::where('key', 'about')->first()->publish();
    }

    public function test_the_old_dutch_about_url_redirects_permanently(): void
    {
        $this->get('/nl/over-ons')->assertStatus(301)->assertRedirect('/nl/over-mij');
        $this->get('/nl/over-mij')->assertOk();
        $this->get('/en/about')->assertOk();
    }

    public function test_the_about_text_is_saved_as_a_draft_and_the_live_page_is_untouched(): void
    {
        $this->artisan('cms:draft-about')->assertSuccessful();

        $about = Page::where('key', 'about')->first();
        $this->assertTrue($about->hasDraftChanges());
        $this->get('/nl/over-mij')->assertDontSee('voor klanten in heel Nederland');

        $this->artisan('cms:draft-about')->assertFailed();
        $this->artisan('cms:draft-about --force')->assertSuccessful();

        $this->assertSame('Over mij', NavigationItem::where('page_key', 'about')->first()->label['nl']);
    }

    public function test_the_published_about_page_answers_who_amir_jebbari_is_in_plain_html(): void
    {
        $this->draftAndPublishAbout();

        $nl = $this->get('/nl/over-mij')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#<h1[^>]*>.*Amir Jebbari.*</h1>#s', $nl);
        $this->assertSame(1, substr_count($nl, '<h1'));
        $this->assertStringContainsString('<title>Amir Jebbari · Oprichter van Jebbari, Amsterdam en Zwolle</title>', $nl);
        $this->assertStringContainsString(self::BIO_NL, $nl);
        $this->assertStringContainsString('href="https://amirs.life"', $nl);
        $this->assertStringContainsString('barbercuts.nl', $nl);
        $this->assertStringContainsString('SaaS-platform om servers en online omgevingen te monitoren', $nl);

        $en = $this->get('/en/about')->assertOk()->getContent();
        $this->assertStringContainsString(self::BIO_EN, $en);
        $this->assertStringContainsString('<link rel="alternate" hreflang="nl" href="'.url('/nl/over-mij').'">', $en);

        foreach ([$nl, $en] as $html) {
            foreach (['JF Cuts', 'Iran', 'alumniOf', 'hasCredential', 'opleiding', 'education'] as $banned) {
                $this->assertStringNotContainsStringIgnoringCase($banned, $html);
            }
        }
    }

    public function test_every_page_describes_the_founder_and_the_organization_with_linked_ids(): void
    {
        Settings::save('social', ['links' => [['label' => 'GitHub', 'url' => 'https://github.example.test/amir']]]);

        foreach (['/nl', '/en/work', '/nl/contact'] as $url) {
            $data = collect($this->jsonLd($this->get($url)->getContent()));

            $person = $data->firstWhere('@type', 'Person');
            $org = $data->firstWhere('@type', 'ProfessionalService');
            $site = $data->firstWhere('@type', 'WebSite');

            $this->assertSame('Amir Jebbari', $person['name']);
            $this->assertSame(url('/').'/#amir', $person['@id']);
            $this->assertSame(['@id' => $org['@id']], $person['worksFor']);
            $this->assertSame(['@id' => $person['@id']], $org['founder']);
            $this->assertSame(['@id' => $org['@id']], $site['publisher']);
            $this->assertSame('Amstelveen', $org['location']['address']['addressLocality']);
            $this->assertSame(['https://amirs.life', 'https://github.example.test/amir'], $person['sameAs']);
            $this->assertArrayNotHasKey('alumniOf', $person);
        }
    }

    public function test_the_about_page_is_a_profile_page_about_the_founder(): void
    {
        $this->draftAndPublishAbout();

        $data = collect($this->jsonLd($this->get('/en/about')->getContent()));

        $this->assertSame(['@id' => url('/').'/#amir'], $data->firstWhere('@type', 'ProfilePage')['mainEntity']);
        $this->assertSame(url('/en/about'), $data->firstWhere('@type', 'Person')['url']);
    }

    public function test_robots_txt_welcomes_ai_crawlers_but_keeps_the_admin_private(): void
    {
        $robots = $this->get('/robots.txt')->assertOk()->getContent();

        foreach (['GPTBot', 'ClaudeBot', 'PerplexityBot', 'Google-Extended'] as $bot) {
            $this->assertStringContainsString('User-agent: '.$bot."\n", $robots);
        }
        $this->assertStringContainsString("Allow: /\nDisallow: /admin\nDisallow: /preview\n", $robots);
        $this->assertSame(1, substr_count($robots, 'Disallow: /admin'), 'one shared group, so every bot gets the admin rule');
    }

    public function test_llms_txt_gives_the_bio_and_only_live_pages(): void
    {
        Page::where('key', 'blog')->first()->unpublish();

        $llms = $this->get('/llms.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')->getContent();

        $this->assertStringStartsWith("# Amir Jebbari\n\n> ".self::BIO_EN, $llms);
        $this->assertStringContainsString(self::BIO_NL, $llms);
        $this->assertStringContainsString(']('.url('/nl/over-mij').')', $llms);
        $this->assertStringContainsString(']('.url('/en/about').')', $llms);
        $this->assertStringContainsString('[amirs.life](https://amirs.life)', $llms);
        $this->assertStringNotContainsString(url('/en/blog'), $llms);
    }

    public function test_articles_are_credited_to_amir_jebbari_in_html_json_ld_and_llms_txt(): void
    {
        $this->draftAndPublishAbout();
        $post = $this->makePost();

        $html = $this->get("/nl/blog/{$post->slug_nl}")->assertOk()->getContent();
        $this->assertStringContainsString('Door <a href="'.url('/nl/over-mij').'" class="link link-bare" rel="author">Amir Jebbari</a>', $html);

        $article = collect($this->jsonLd($html))->firstWhere('@type', 'BlogPosting');
        $this->assertSame('Person', $article['author']['@type']);
        $this->assertSame(url('/').'/#amir', $article['author']['@id']);
        $this->assertSame(url('/').'/#org', $article['publisher']['@id']);

        $this->assertStringContainsString('- [An article]('.url("/en/blog/{$post->slug_en}").'): Short excerpt.', $this->get('/llms.txt')->getContent());
    }

    public function test_the_one_address_is_amstelveen_in_the_footer_json_ld_and_llms_txt(): void
    {
        $this->publishSite();
        $html = $this->get('/nl')->getContent();

        $this->assertStringContainsString('Professor W.H. Keesomlaan 12', $html);
        $this->assertStringContainsString('1183 DJ Amstelveen', $html);

        $org = collect($this->jsonLd($html))->firstWhere('@type', 'ProfessionalService');
        $address = ['@type' => 'PostalAddress', 'streetAddress' => 'Professor W.H. Keesomlaan 12', 'postalCode' => '1183 DJ', 'addressLocality' => 'Amstelveen', 'addressCountry' => 'NL'];
        $this->assertSame($address, $org['address']);
        $this->assertSame($address, $org['location']['address']);
        $this->assertArrayNotHasKey('homeLocation', collect($this->jsonLd($html))->firstWhere('@type', 'Person'));

        $this->assertStringContainsString('Professor W.H. Keesomlaan 12, 1183 DJ Amstelveen, The Netherlands', $this->get('/llms.txt')->getContent());
    }

    public function test_projects_are_credited_to_amir_and_llms_txt_describes_related_sites(): void
    {
        $this->publishSite();
        $project = $this->makeProject();

        $work = collect($this->jsonLd($this->get("/en/work/{$project->slug_en}")->getContent()))->firstWhere('@type', 'CreativeWork');
        $this->assertSame(rtrim(url('/'), '/').'/#amir', $work['creator']['@id']);
        $this->assertSame(rtrim(url('/'), '/').'/#org', $work['publisher']['@id']);

        $txt = $this->get('/llms.txt')->getContent();
        $this->assertStringContainsString('[barbercuts.nl](https://barbercuts.nl): Mobile-first booking web app for a barber, built by Amir Jebbari', $txt);
        $this->assertStringContainsString("User-agent: Googlebot\n", $this->get('/robots.txt')->getContent());
    }

    public function test_no_public_page_mentions_education_or_the_sensitive_project(): void
    {
        $this->publishSite();
        $this->artisan('cms:draft-about');
        Page::where('key', 'about')->first()->publish();

        foreach (['/nl', '/en', '/nl/over-mij', '/en/about', '/nl/werk', '/llms.txt'] as $url) {
            $html = mb_strtolower($this->get($url)->getContent());
            foreach (['opleiding', 'diploma', 'universiteit', 'hogeschool', 'education', 'university', 'alumni', 'hascredential', 'iran'] as $word) {
                $this->assertStringNotContainsString($word, $html, "$url mentions '$word'");
            }
        }
    }
}
