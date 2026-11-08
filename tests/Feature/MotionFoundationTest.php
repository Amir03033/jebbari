<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PublishesSite;
use Tests\TestCase;

class MotionFoundationTest extends TestCase
{
    use PublishesSite, RefreshDatabase;

    public function test_pages_load_the_js_flag_script_before_the_bundle(): void
    {
        $this->publishSite();
        $html = $this->get('/en/work')->assertOk()->getContent();

        $this->assertStringContainsString('js/motion-flag.js', $html);
        $this->assertLessThan(strpos($html, 'rel="stylesheet"'), strpos($html, 'js/motion-flag.js'));
        $this->assertFileExists(public_path('js/motion-flag.js'));
    }

    public function test_content_is_in_the_html_without_javascript(): void
    {
        // Reveals only hide content under html.js, so the markup must contain the full text.
        $this->get('/_style')->assertOk()
            ->assertSee('data-reveal="group"', false)
            ->assertSee('properly', false)
            ->assertSee('class="stop text-signal"', false);
    }

    public function test_hero_lines_are_split_on_the_server(): void
    {
        $this->get('/_style')->assertSee('<span class="line"><span class="line-inner">Software, done</span></span>', false);
    }
}
