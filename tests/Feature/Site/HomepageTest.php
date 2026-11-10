<?php

namespace Tests\Feature\Site;

use App\Models\Page;
use App\Models\Project;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsSiteContent;
use Tests\Support\PublishesSite;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use BuildsSiteContent, PublishesSite, RefreshDatabase;

    private function home(): Page
    {
        return Page::where('key', 'home')->firstOrFail();
    }

    public function test_the_hero_comes_from_the_cms_in_each_language(): void
    {
        $this->publishSite();

        $nl = $this->get('/nl')->assertOk();
        $nl->assertSee('Softwarestudio, Zwolle &amp; Amsterdam', false)
            ->assertSee('<span class="line"><span class="line-inner">Software</span></span>', false)
            ->assertSee('die klopt<span class="stop text-signal">.</span>', false)
            ->assertSee('Wij ontwerpen en bouwen webapplicaties')
            ->assertSee('Bekijk ons werk')
            ->assertSee('Neem contact op');

        $this->get('/en')->assertSee('Software, done')->assertSee('properly<span class="stop text-signal">.</span>', false)
            ->assertSee('View our work')->assertSee('Get in touch');
    }

    public function test_editing_the_hero_in_the_cms_changes_the_page_only_after_publishing(): void
    {
        $this->publishSite();
        $home = $this->home();
        $blocks = $home->blocks;
        $blocks[0]['data']['headline']['nl'] = "Nieuwe kop\nvoor de site";
        $home->saveFromForm(['blocks' => $blocks]);

        $this->get('/nl')->assertSee('die klopt')->assertDontSee('Nieuwe kop');

        $home->fresh()->publish();

        $this->get('/nl')->assertSee('Nieuwe kop')->assertSee('voor de site')->assertDontSee('die klopt');
    }

    public function test_hero_buttons_only_link_to_pages_that_are_live(): void
    {
        $this->publishSite(['home', 'contact']);

        $html = $this->get('/nl')->assertOk()->getContent();

        $this->assertStringContainsString('href="'.url('/nl/contact').'"', $html);
        $this->assertStringNotContainsString('Bekijk ons werk', $html, 'work page is not published, so its button is omitted');
    }

    public function test_the_hidden_about_teaser_is_not_shown_and_appears_once_written(): void
    {
        $this->publishSite();
        $this->get('/nl')->assertDontSee('Over mij');

        $home = $this->home();
        $blocks = collect($home->blocks)->map(function ($b) {
            if ($b['type'] === 'about_teaser') {
                $b['data'] = ['visible' => true, 'label' => ['nl' => 'Over mij', 'en' => 'About'], 'heading' => ['nl' => 'Ik bouw websites', 'en' => 'I build websites'], 'body' => ['nl' => 'Een eerste alinea.', 'en' => 'A first paragraph.']];
            }

            return $b;
        })->all();
        $home->saveFromForm(['blocks' => $blocks]);
        $home->fresh()->publish();

        $this->get('/nl')->assertSee('Ik bouw websites')->assertSee('Een eerste alinea.')->assertSee('Meer over mij');
        $this->get('/en')->assertSee('I build websites')->assertSee('More about me');
    }

    public function test_an_about_teaser_without_text_is_omitted_even_when_visible(): void
    {
        $this->publishSite();
        $home = $this->home();
        $blocks = collect($home->blocks)->map(fn ($b) => $b['type'] === 'about_teaser' ? array_replace_recursive($b, ['data' => ['visible' => true]]) : $b)->all();
        $home->forceFill(['blocks' => $blocks])->saveQuietly();

        $this->get('/nl')->assertOk()->assertDontSee('Over mij');
    }

    public function test_services_show_only_published_services_in_cms_order(): void
    {
        $this->publishSite();
        $this->makeService(['title' => ['nl' => 'Tweede', 'en' => 'Second'], 'sort' => 20]);
        $this->makeService(['title' => ['nl' => 'Eerste', 'en' => 'First'], 'sort' => 10]);
        $this->makeService(['title' => ['nl' => 'Concept', 'en' => 'Draft'], 'sort' => 5], publish: false);

        $html = $this->get('/nl')->assertOk()->getContent();

        $this->assertLessThan(strpos($html, 'Tweede'), strpos($html, 'Eerste'));
        $this->assertStringNotContainsString('Concept', $html);
    }

    public function test_the_services_section_is_omitted_while_nothing_is_published(): void
    {
        $this->publishSite();

        $this->get('/nl')->assertOk()->assertDontSee('service-row', false)->assertDontSee('/ DIENSTEN');
    }

    public function test_selected_work_shows_published_featured_projects_only(): void
    {
        $this->publishSite();
        $this->makeProject(['title' => ['nl' => 'Uitgelicht', 'en' => 'Featured']]);
        $this->makeProject(['title' => ['nl' => 'Niet uitgelicht', 'en' => 'Not featured'], 'is_featured' => false]);
        $this->makeProject(['title' => ['nl' => 'Nog concept', 'en' => 'Still draft']], publish: false);

        $home = $this->body('/nl');
        $this->assertStringContainsString('Uitgelicht', $home);
        $this->assertStringNotContainsString('Niet uitgelicht', $home);
        $this->assertStringNotContainsString('Nog concept', $home);

        $this->get('/nl/werk')->assertSee('Uitgelicht')->assertSee('Niet uitgelicht')->assertDontSee('Nog concept');
    }

    public function test_serverscope_appears_with_only_its_verified_facts(): void
    {
        $this->publishSite();
        Project::where('slug_en', 'serverscope')->firstOrFail()->publish();

        $this->get('/nl')->assertSee('ServerScope')->assertSee('Servermonitoring-dashboard');

        $page = $this->get('/nl/werk/serverscope')->assertOk();
        $page->assertSee('ServerScope')->assertSee('Servermonitoring-dashboard');
        // Nothing is made up: no year, client, outcome or links appear.
        $page->assertDontSee('De opdracht')->assertDontSee('Resultaat')->assertDontSee('Klant')->assertDontSee('Jaar');
    }

    public function test_the_process_section_renders_the_cms_steps_with_the_timeline_markup(): void
    {
        $this->publishSite();

        $this->get('/nl')->assertSee('data-process', false)->assertSee('process-fill', false)
            ->assertSee('Praten')->assertSee('Ontwerpen')->assertSee('Bouwen')->assertSee('Lanceren');
        $this->get('/en')->assertSee('Talk')->assertSee('Design')->assertSee('Build')->assertSee('Launch');
    }

    public function test_section_order_and_visibility_follow_the_cms(): void
    {
        $this->publishSite();
        $this->makeService();
        $home = $this->home();

        $blocks = collect($home->blocks);
        $first = fn (string $type) => $blocks->firstWhere('type', $type);
        $rest = $blocks->reject(fn ($b) => in_array($b['type'], ['hero', 'process', 'services_overview']))->all();
        $home->forceFill(['blocks' => [$first('hero'), $first('process'), $first('services_overview'), ...$rest]])->saveQuietly();

        $html = $this->get('/nl')->getContent();
        $this->assertLessThan(strpos($html, 'line-inner">Diensten<'), strpos($html, 'line-inner">Werkwijze<'), 'process now comes before services');

        // Hiding a block removes it and the numbers stay sequential.
        $hidden = collect($home->fresh()->blocks)->map(fn ($b) => $b['type'] === 'process' ? array_replace_recursive($b, ['data' => ['visible' => false]]) : $b)->all();
        $home->forceFill(['blocks' => $hidden])->saveQuietly();

        $html = $this->get('/nl')->getContent();
        $this->assertStringNotContainsString('Praten', $html);
        // Sections are numbered 01 (hero), 02, 03 ... with no gap where blocks are hidden or empty.
        $this->assertMatchesRegularExpression('#label-row text-muted">02\b.*?line-inner">Diensten<#s', $html);
        $this->assertMatchesRegularExpression('#>03</span> / Contact#', $html);
    }

    public function test_the_closing_call_to_action_uses_the_email_from_settings_and_omits_it_when_missing(): void
    {
        $this->publishSite();

        $html = $this->get('/nl')->assertSee('Iets te bouwen?')->getContent();
        $this->assertStringNotContainsString('mailto:', $html, 'no email entered yet, so none is invented');

        Settings::save('contact', ['email' => 'studio@voorbeeld.test', 'booking_url' => 'https://cal.example.test/jebbari']);

        $this->get('/nl')->assertSee('mailto:studio@voorbeeld.test', false)->assertSee('Plan een gesprek')->assertSee('https://cal.example.test/jebbari', false);
    }

    public function test_no_placeholder_text_ever_reaches_the_public_pages(): void
    {
        $this->publishSite();
        $this->makeService();
        Project::where('slug_en', 'serverscope')->firstOrFail()->publish();

        foreach (['/nl', '/en', '/nl/werk', '/nl/diensten', '/nl/over-ons', '/nl/blog', '/nl/contact', '/nl/werk/serverscope'] as $url) {
            $this->get($url)->assertOk()->assertDontSee('[[')->assertDontSee('OWNER')->assertDontSee('Lorem');
        }
    }

    public function test_the_spec_sheet_is_hidden_until_real_measurements_exist(): void
    {
        $this->publishSite();

        $this->get('/en')->assertDontSee('Built to a standard');

        config(['specs.measured_at' => '2026-11-10', 'specs.values' => [['label' => ['nl' => 'Derden', 'en' => 'Third-party requests'], 'value' => '0', 'note' => ['nl' => '', 'en' => '']]]]);

        $this->get('/en')->assertSee('Built to a standard')->assertSee('Third-party requests')->assertSee('2026-11-10');
    }

    public function test_the_header_shows_the_brand_descriptor_status_and_cms_navigation(): void
    {
        $this->publishSite();
        Settings::save('header', ['taking_projects' => false, 'status_closed' => ['nl' => 'Even volgeboekt', 'en' => 'Fully booked for now']]);

        $this->get('/nl')->assertSee('Softwareontwikkelaar &amp; ondernemer', false)->assertSee('Even volgeboekt')
            ->assertSee('href="'.url('/nl/werk').'"', false)->assertSee('href="'.url('/nl/blog').'"', false);
        $this->get('/en')->assertSee('Software developer &amp; entrepreneur', false)->assertSee('Fully booked for now');
    }

    public function test_navigation_items_whose_page_is_not_live_are_hidden(): void
    {
        $this->publishSite(['home', 'work', 'contact']);

        $html = $this->get('/nl')->getContent();
        $this->assertStringContainsString(url('/nl/werk'), $html);
        $this->assertStringNotContainsString(url('/nl/diensten'), $html);
        $this->assertStringNotContainsString(url('/nl/blog'), $html);
    }

    public function test_the_footer_shows_company_details_only_when_provided(): void
    {
        $this->publishSite();

        $html = $this->get('/nl')->getContent();
        $this->assertStringNotContainsString('KvK', $html);
        $this->assertStringNotContainsString('BTW', $html);

        Settings::save('company', ['legal_name' => 'Jebbari Testbedrijf', 'kvk' => '12345678', 'btw' => 'NL000000000B01']);
        Settings::save('social', ['links' => [['label' => 'LinkedIn', 'url' => 'https://linkedin.example.test/jebbari']]]);

        $this->get('/nl')->assertSee('Jebbari Testbedrijf')->assertSee('KvK 12345678')->assertSee('BTW NL000000000B01')
            ->assertSee('LinkedIn')->assertSee('https://linkedin.example.test/jebbari', false);
    }
}
