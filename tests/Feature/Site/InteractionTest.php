<?php

namespace Tests\Feature\Site;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsSiteContent;
use Tests\Support\PublishesSite;
use Tests\TestCase;

class InteractionTest extends TestCase
{
    use BuildsSiteContent, PublishesSite, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publishSite();
    }

    private function addHomeBlock(array $block): void
    {
        $home = Page::where('key', 'home')->firstOrFail();
        $blocks = $home->blocks;
        array_splice($blocks, 2, 0, [$block]);
        $home->blocks = $blocks;
        $home->save();
    }

    public function test_the_footer_has_a_live_status_panel_that_measures_real_addresses(): void
    {
        $html = $this->get('/nl')->assertOk()->getContent();

        $this->assertStringContainsString('data-live', $html);
        $this->assertSame(3, substr_count($html, 'data-ping="'), 'site, pages and admin are measured');
        $this->assertStringContainsString('data-ping="'.url('/up').'"', $html);
        $this->assertStringContainsString('Echt gemeten', $html, 'the panel says it is measured, not simulated');
        $this->get('/up')->assertOk();
        $this->get('/en')->assertSee('Really measured');
    }

    public function test_the_theme_switch_is_in_the_header_and_the_saved_choice_is_applied_before_paint(): void
    {
        $this->get('/nl')->assertSee('data-theme-toggle', false)->assertSee('prefers-color-scheme: dark', false);
        $this->assertStringContainsString("localStorage.getItem('theme')", file_get_contents(public_path('js/motion-flag.js')));
    }

    public function test_the_technology_marquee_shows_real_technologies_only_when_there_are_enough(): void
    {
        $this->addHomeBlock(['type' => 'tech_marquee', 'data' => ['visible' => true]]);

        $this->makeProject(['stack' => ['Laravel', 'Alpine']]);
        $this->get('/nl')->assertDontSee('class="marquee"', false);

        $this->makeProject(['title' => ['nl' => 'Twee', 'en' => 'Two'], 'stack' => ['Vite', 'Laravel']]);
        $html = $this->get('/nl')->assertSee('class="marquee"', false)->getContent();
        $this->assertStringContainsString('Vite', $html);
        $this->assertSame(1, substr_count($html, '<li>Laravel</li>'), 'each technology once in the accessible list');
    }

    public function test_the_stats_block_shows_the_owners_numbers_and_nothing_when_empty(): void
    {
        $this->addHomeBlock(['type' => 'stats', 'data' => ['visible' => true, 'label' => ['nl' => 'In cijfers', 'en' => 'In numbers'], 'items' => []]]);
        $this->get('/nl')->assertDontSee('class="stats"', false);

        $this->addHomeBlock(['type' => 'stats', 'data' => ['visible' => true, 'label' => ['nl' => 'In cijfers', 'en' => 'In numbers'],
            'items' => [['value' => 12, 'suffix' => '+', 'label' => ['nl' => 'Jaar ervaring', 'en' => 'Years of experience']]]]]);

        $this->get('/nl')->assertSee('class="stats"', false)->assertSee('data-count="12"', false)->assertSee('Jaar ervaring');
        $this->get('/en')->assertSee('Years of experience')->assertSee('In numbers');
    }

    public function test_several_projects_become_a_card_stack_and_one_project_stays_a_grid(): void
    {
        $this->makeProject();
        $this->get('/nl')->assertDontSee('data-stack', false);

        $this->makeProject(['title' => ['nl' => 'Tweede', 'en' => 'Second']]);
        $html = $this->get('/nl')->assertSee('data-stack', false)->getContent();
        $this->assertSame(2, substr_count($html, 'class="stack-item" data-scroll="stack"'));
    }

    public function test_the_homepage_carries_the_motion_hooks_without_inline_styles(): void
    {
        $html = $this->get('/nl')->getContent();

        foreach (['data-scroll="exit"', 'data-scroll="enter"', 'data-field', 'data-nav'] as $hook) {
            $this->assertStringContainsString($hook, $html, $hook);
        }
        $this->assertDoesNotMatchRegularExpression('/\sstyle="/i', $html, 'CSP: no inline style attributes');
    }

    public function test_the_contact_confirmation_has_an_animated_mark_and_keeps_its_text(): void
    {
        $this->withSession(['inquiry_sent' => true])->get('/nl/contact')
            ->assertSee('class="sent-mark"', false)->assertSee('Bedankt. Je bericht is aangekomen.');
    }
}
