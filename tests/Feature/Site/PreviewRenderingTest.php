<?php

namespace Tests\Feature\Site;

use App\Models\Page;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsSiteContent;
use Tests\Support\CreatesUsers;
use Tests\Support\PublishesSite;
use Tests\TestCase;

class PreviewRenderingTest extends TestCase
{
    use BuildsSiteContent, CreatesUsers, PublishesSite, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publishSite();
    }

    public function test_a_draft_service_previews_in_the_real_design(): void
    {
        $service = $this->makeService(['title' => ['nl' => 'Alleen concept', 'en' => 'Draft only']], publish: false);
        $this->actingAs($this->editor());

        $html = $this->get($service->previewUrl('nl'))->assertOk()->assertSee('Alleen concept')->getContent();

        $this->assertStringContainsString('t-display', $html, 'rendered with the real page template');
        $this->assertStringContainsString('<header', $html);
        $this->assertStringContainsString('Voorbeeld, niet openbaar', $html);
        $this->assertStringContainsString('Concept, niet gepubliceerd', $html);
        $this->get('/nl/diensten/'.$service->slug_nl)->assertNotFound();
    }

    public function test_unpublished_changes_preview_while_the_live_page_stays_unchanged(): void
    {
        $service = $this->makeService();
        $service->fresh()->saveFromForm(['title' => ['nl' => 'Nieuwe titel', 'en' => 'New title'], 'benefits' => ['nl' => 'Nieuw voordeel', 'en' => 'New benefit']]);
        $service = $service->fresh();
        $this->actingAs($this->editor());

        $this->get($service->previewUrl('nl'))->assertSee('Nieuwe titel')->assertSee('Nieuw voordeel')->assertSee('Gepubliceerd, met niet-gepubliceerde wijzigingen');
        $this->get($service->previewUrl('en'))->assertSee('New title')->assertSee('Published, with unpublished changes');

        $this->get("/nl/diensten/{$service->slug_nl}")->assertSee('Webbouw')->assertDontSee('Nieuwe titel')->assertDontSee('Nieuw voordeel');
    }

    public function test_the_homepage_draft_previews_with_new_sections_and_hides_nothing_from_the_live_site(): void
    {
        $home = Page::where('key', 'home')->first();
        $blocks = $home->blocks;
        $blocks[0]['data']['headline']['nl'] = "Concept kop\nhier";
        $home->saveFromForm(['blocks' => $blocks]);
        $this->actingAs($this->owner());

        $this->get($home->fresh()->previewUrl('nl'))->assertSee('Concept kop')->assertSee('Voorbeeld, niet openbaar');
        $this->get('/nl')->assertDontSee('Concept kop')->assertSee('die klopt');
    }

    public function test_an_unpublished_page_can_be_previewed(): void
    {
        Page::where('key', 'about')->first()->unpublish();
        $about = Page::where('key', 'about')->first();
        $this->actingAs($this->owner());

        $this->get('/nl/over-mij')->assertNotFound();
        $this->get($about->previewUrl('nl'))->assertOk()->assertSee('Amir Jebbari');
    }

    public function test_project_and_post_previews_use_their_real_templates(): void
    {
        $project = $this->makeProject(['title' => ['nl' => 'Conceptproject', 'en' => 'Draft project']], publish: false);
        $post = $this->makePost(['title' => ['nl' => 'Conceptartikel', 'en' => 'Draft article']], publish: false);
        $this->actingAs($this->owner());

        $this->get($project->previewUrl('nl'))->assertOk()->assertSee('Conceptproject')->assertSee('Webapplicatie');
        $this->get($post->previewUrl('en'))->assertOk()->assertSee('Draft article')->assertSee('English text.');
    }

    public function test_previews_are_private_not_indexed_and_not_cached(): void
    {
        $service = $this->makeService(publish: false);
        $this->actingAs($this->editor());

        $response = $this->get($service->previewUrl('nl'))->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('noindex', $response->headers->get('X-Robots-Tag'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $html);
        $this->assertStringNotContainsString('rel="canonical"', $html);
        $this->assertStringNotContainsString('og:title', $html);
    }

    public function test_the_language_switch_in_a_preview_stays_inside_the_preview(): void
    {
        $service = $this->makeService(publish: false);
        $this->actingAs($this->editor());

        $html = $this->get($service->previewUrl('nl'))->getContent();

        $this->assertMatchesRegularExpression('#href="'.preg_quote(url('/preview/service/'.$service->id), '#').'\?[^"]*lang=en[^"]*signature=[^"]+"#', html_entity_decode($html));
    }

    public function test_previews_still_require_a_signed_link_and_an_admin(): void
    {
        $service = $this->makeService(publish: false);

        $this->get($service->previewUrl())->assertRedirect('/admin/login');

        $this->actingAs($this->editor());
        $this->get('/preview/service/'.$service->id)->assertForbidden();
        $this->actingAs($this->support())->get($service->previewUrl())->assertForbidden();
    }

    public function test_previews_never_bring_other_drafts_into_the_site(): void
    {
        $service = $this->makeService(['title' => ['nl' => 'Preview mij', 'en' => 'Preview me']], publish: false);
        $this->makeService(['title' => ['nl' => 'Ander concept', 'en' => 'Other draft']], publish: false);
        $this->actingAs($this->owner());

        $this->get($service->previewUrl('nl'))->assertOk()->assertDontSee('Ander concept');
    }

    public function test_testimonials_without_a_page_fall_back_to_a_content_view(): void
    {
        $t = Testimonial::create(['name' => 'Klant', 'quote' => ['nl' => 'Heel goed.', 'en' => 'Very good.']]);
        $this->actingAs($this->owner());

        $this->get($t->previewUrl('nl'))->assertOk()->assertSee('Heel goed.');
    }

    public function test_testimonials_appear_publicly_only_when_published_featured_and_permitted(): void
    {
        $home = Page::where('key', 'home')->first();
        $blocks = $home->blocks;
        array_splice($blocks, 1, 0, [['type' => 'testimonials', 'data' => ['visible' => true, 'label' => ['nl' => 'Referenties', 'en' => 'References'], 'mode' => 'featured']]]);
        $home->forceFill(['blocks' => $blocks])->saveQuietly();

        $this->get('/nl')->assertDontSee('Referenties');

        $t = Testimonial::create(['name' => 'Echte Klant', 'company' => 'Voorbeeld BV', 'quote' => ['nl' => 'Prima samenwerking.', 'en' => 'Great collaboration.'], 'is_featured' => true, 'permission_confirmed' => true]);
        $this->get('/nl')->assertDontSee('Prima samenwerking.');

        $t->publish();
        $this->get('/nl')->assertSee('Referenties')->assertSee('Prima samenwerking.')->assertSee('Echte Klant, Voorbeeld BV');
        $this->get('/en')->assertSee('Great collaboration.');

        $t->forceFill(['permission_confirmed' => false])->saveQuietly();
        $this->get('/nl')->assertDontSee('Prima samenwerking.');
    }
}
