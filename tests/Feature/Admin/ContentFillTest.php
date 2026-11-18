<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PublishesSite;
use Tests\TestCase;

class ContentFillTest extends TestCase
{
    use PublishesSite, RefreshDatabase;

    /** The state of jebbari.nl as read on 18 November 2026, in miniature. */
    private function liveLikeContent(): void
    {
        $this->publishSite();

        foreach (['uses' => [['type' => 'gear', 'data' => ['visible' => true, 'label' => ['nl' => 'Servers', 'en' => 'Servers'], 'items' => [['name' => ['nl' => 'Linux', 'en' => 'Linux'], 'description' => ['nl' => 'Linux.', 'en' => 'Linux.']]]]]],
            'cv' => [['type' => 'intro', 'data' => ['visible' => true, 'heading' => ['nl' => 'Cv', 'en' => 'CV'], 'body' => ['nl' => 'Cv.', 'en' => 'CV.']]]]] as $key => $blocks) {
            Page::create(['key' => $key, 'kind' => 'system', 'title' => ['nl' => ucfirst($key), 'en' => ucfirst($key)], 'blocks' => $blocks])->publish();
        }

        $about = Page::where('key', 'about')->first();
        $about->forceFill(['blocks' => [
            ['type' => 'intro', 'data' => ['visible' => true, 'heading' => ['nl' => 'Amir Jebbari', 'en' => 'Amir Jebbari'], 'body' => ['nl' => 'Over mij.', 'en' => 'About me.']]],
            ['type' => 'process', 'data' => ['visible' => true, 'steps' => [['title' => ['nl' => 'Praten', 'en' => 'Talk']]]]],
        ]])->save();

        $home = Page::where('key', 'home')->first();
        $home->forceFill(['seo' => ['description' => ['nl' => 'Jebbari is een onafhankelijk digital studio. Wij bouwen websites.', 'en' => 'We build websites.']]])->save();

        $service = Service::firstOrNew(['slug_en' => 'seo-performance-optimization']);
        $service->forceFill(['title' => ['nl' => 'SEO', 'en' => 'SEO'], 'slug_nl' => 'seo-websiteoptimalisatie',
            'summary' => ['nl' => 'Wij optimaliseren websites.', 'en' => 'We optimise websites.'], 'description' => ['nl' => '<p>Wij helpen.</p>', 'en' => '<p>We help.</p>']])->save();
        $service->publish();

        $mine = Service::firstOrNew(['slug_en' => 'website-development']);
        $mine->forceFill(['title' => ['nl' => 'Websites', 'en' => 'Websites'], 'slug_nl' => 'website-ontwikkeling',
            'summary' => ['nl' => 'Ik bouw websites.', 'en' => 'I build websites.'], 'description' => ['nl' => '<p>Ik bouw.</p>', 'en' => '<p>I build.</p>']])->save();
        $mine->publish();

        Project::query()->delete(); // the live site has exactly these, not the seeded ServerScope

        foreach ([
            ['BarberCuts – Slim afspraken- en salonbeheer', 'https://barbercuts.nl', null],
            ['ServerScope – Server- en infrastructuurmonitoring', 'https://serverscope.nl', ['nl' => 'Een eigen platform.', 'en' => 'An own platform.']],
            ['ReviewsPilot – Review- en reputatiemanagementplatform', null, null],
        ] as [$title, $url, $outcome]) {
            $p = Project::create(['title' => ['nl' => $title, 'en' => $title], 'kind' => ['nl' => 'Product', 'en' => 'Product'], 'url' => $url, 'outcome' => $outcome]);
            $p->publish();
        }
    }

    public function test_it_fills_only_the_gaps_as_unpublished_changes(): void
    {
        $this->liveLikeContent();

        $this->artisan('content:fill')->assertSuccessful()
            ->expectsOutputToContain('Over mij: vaardigheden toegevoegd')
            ->expectsOutputToContain('ReviewsPilot: link naar de live site ontbreekt');

        // Nothing visible changed yet.
        $this->get('/nl/cv')->assertDontSee('Webapplicaties en SaaS');
        $this->get('/nl/diensten/seo-websiteoptimalisatie')->assertSee('Wij optimaliseren websites.');

        // The proposals are waiting as drafts.
        $about = Page::where('key', 'about')->first();
        $this->assertTrue($about->hasDraftChanges());
        $this->assertSame(['intro', 'skills', 'process'], array_column($about->withDraft()->blocks, 'type'));

        $seo = Service::where('slug_en', 'seo-performance-optimization')->first()->withDraft();
        $this->assertStringStartsWith('Ik optimaliseer', $seo->summary['nl']);
        $this->assertFalse(Service::where('slug_en', 'website-development')->first()->hasDraftChanges());

        $this->assertStringNotContainsString('Wij', Page::where('key', 'home')->first()->withDraft()->seo['description']['nl']);

        $barber = Project::all()->first(fn ($p) => str_contains($p->title['nl'], 'BarberCuts'));
        $this->assertStringContainsString('barbercuts.nl', $barber->withDraft()->outcome['nl']);
        $this->assertTrue((bool) $barber->withDraft()->monitor);

        $scope = Project::all()->first(fn ($p) => str_contains($p->title['nl'], 'ServerScope'));
        $this->assertSame('Een eigen platform.', $scope->withDraft()->outcome['nl']);
        $this->assertCount(4, $scope->withDraft()->timeline);

        // Publishing makes it live: the CV gets real skills.
        $about->publish();
        $this->get('/nl/cv')->assertSee('Webapplicaties en SaaS')->assertSee('Vue 3, TypeScript');
    }

    public function test_running_it_twice_changes_nothing_more(): void
    {
        $this->liveLikeContent();
        $this->artisan('content:fill');
        $first = Page::where('key', 'about')->first()->draft_data;

        $this->artisan('content:fill')->expectsOutputToContain('Over mij: heeft al vaardigheden, ongewijzigd')
            ->expectsOutputToContain('Dienst seo-performance-optimization: al in de ik-vorm');

        $this->assertEquals($first, Page::where('key', 'about')->first()->draft_data);
    }

    public function test_nothing_it_proposes_contains_an_owner_placeholder_or_banned_words(): void
    {
        $this->liveLikeContent();
        $this->artisan('content:fill');

        // Only what the fill proposes: the unpublished changes.
        $all = json_encode([Page::all()->pluck('draft_data'), Service::all()->pluck('draft_data'), Project::all()->pluck('draft_data')], JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('Vaardigheden', $all);

        $this->assertStringNotContainsString('[[ OWNER', $all);
        foreach (['naadloos', 'seamless', 'cutting-edge', 'Iran Digital Lifeline', 'opleiding', 'education'] as $word) {
            $this->assertStringNotContainsStringIgnoringCase($word, $all);
        }
    }
}
