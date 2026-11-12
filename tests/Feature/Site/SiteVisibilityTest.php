<?php

namespace Tests\Feature\Site;

use App\Models\Page;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsSiteContent;
use Tests\Support\PublishesSite;
use Tests\TestCase;

class SiteVisibilityTest extends TestCase
{
    use BuildsSiteContent, PublishesSite, RefreshDatabase;

    public function test_unpublished_system_pages_are_not_found_in_either_language(): void
    {
        $this->seed(CmsSeeder::class);

        foreach (['/nl', '/en', '/nl/werk', '/en/work', '/nl/diensten', '/nl/over-mij', '/nl/blog', '/nl/contact'] as $url) {
            $this->get($url)->assertNotFound();
        }
    }

    public function test_published_system_pages_render_in_both_languages(): void
    {
        $this->publishSite();

        foreach (['/nl', '/en', '/nl/werk', '/en/work', '/nl/diensten', '/en/services', '/nl/over-mij', '/en/about', '/nl/blog', '/en/blog', '/nl/contact', '/en/contact'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_a_page_missing_its_translation_is_hidden_in_that_language_only(): void
    {
        $this->publishSite();
        $about = Page::where('key', 'about')->first();
        $about->forceFill(['title' => ['nl' => 'Over mij', 'en' => '']])->saveQuietly();

        $this->get('/nl/over-mij')->assertOk();
        $this->get('/en/about')->assertNotFound();
    }

    public function test_drafts_are_never_public_for_every_content_type(): void
    {
        $this->publishSite();
        $service = $this->makeService(publish: false);
        $project = $this->makeProject(publish: false);
        $post = $this->makePost(publish: false);

        $this->get("/nl/diensten/{$service->slug_nl}")->assertNotFound();
        $this->get("/nl/werk/{$project->slug_nl}")->assertNotFound();
        $this->get("/nl/blog/{$post->slug_nl}")->assertNotFound();
        $this->get('/nl/werk')->assertOk()->assertDontSee('Testproject');
        $this->get('/nl/blog')->assertOk()->assertDontSee('Een artikel');
    }

    public function test_publishing_makes_content_public_and_unpublishing_removes_it(): void
    {
        $this->publishSite();
        $service = $this->makeService();

        $this->get("/nl/diensten/{$service->slug_nl}")->assertOk()->assertSee('Webbouw');
        $this->get("/en/services/{$service->slug_en}")->assertOk()->assertSee('Web building');

        $service->fresh()->unpublish();

        $this->get("/nl/diensten/{$service->slug_nl}")->assertNotFound();
    }

    public function test_unpublished_changes_never_appear_publicly(): void
    {
        $this->publishSite();
        $service = $this->makeService();
        $service->fresh()->saveFromForm(['title' => ['nl' => 'Ongepubliceerde titel', 'en' => 'Unpublished title']]);

        $this->get("/nl/diensten/{$service->slug_nl}")->assertOk()->assertSee('Webbouw')->assertDontSee('Ongepubliceerde titel');
        $this->get('/nl/diensten')->assertDontSee('Ongepubliceerde titel');
        $this->get('/sitemap.xml')->assertDontSee('Ongepubliceerde');
    }

    public function test_scheduled_posts_appear_only_after_their_date(): void
    {
        $this->publishSite();
        $post = $this->makePost(['published_at' => now()->addDays(3)]);

        $this->get("/nl/blog/{$post->slug_nl}")->assertNotFound();
        $this->get('/nl/blog')->assertDontSee('Een artikel');

        $this->travel(4)->days();

        $this->get("/nl/blog/{$post->slug_nl}")->assertOk()->assertSee('Een artikel');
    }

    public function test_deleted_posts_are_gone(): void
    {
        $this->publishSite();
        $post = $this->makePost();
        $post->delete();

        $this->get("/nl/blog/{$post->slug_nl}")->assertNotFound();
    }

    public function test_content_missing_its_english_text_is_hidden_in_english_but_not_dutch(): void
    {
        $this->publishSite();
        $project = $this->makeProject();
        $project->forceFill(['title' => ['nl' => 'Testproject', 'en' => '']])->saveQuietly();

        $this->get("/nl/werk/{$project->slug_nl}")->assertOk();
        $this->get("/en/work/{$project->slug_en}")->assertNotFound();
        $this->get('/en/work')->assertOk()->assertDontSee('Test project');
        $this->assertStringNotContainsString("/en/work/{$project->slug_en}", $this->get('/sitemap.xml')->getContent());
    }

    public function test_slugs_do_not_work_in_the_wrong_language(): void
    {
        $this->publishSite();
        $service = $this->makeService();

        $this->get("/en/services/{$service->slug_nl}")->assertNotFound();
        $this->get("/nl/diensten/{$service->slug_en}")->assertNotFound();
    }

    public function test_unknown_urls_show_the_friendly_error_page_in_the_right_language(): void
    {
        $this->publishSite();

        $this->get('/nl/bestaat-niet')->assertNotFound()->assertSee('Pagina niet gevonden');
        $this->get('/en/does-not-exist')->assertNotFound()->assertSee('Page not found');
    }

    public function test_legal_pages_appear_only_when_published_and_free_of_placeholders(): void
    {
        $this->publishSite();
        $privacy = Page::where('key', 'privacy')->first();

        $this->get("/nl/{$privacy->slug_nl}")->assertNotFound();

        $this->assertNotEmpty($privacy->publishIssues(), 'the seeded draft still has owner placeholders');

        $privacy->update(['blocks' => [['type' => 'rich_text', 'data' => ['visible' => true, 'body' => ['nl' => '<h2>Privacy</h2><p>Echte tekst.</p>', 'en' => '<h2>Privacy</h2><p>Real text.</p>']]]]]);
        $privacy->fresh()->publish();

        $this->get("/nl/{$privacy->slug_nl}")->assertOk()->assertSee('Echte tekst.')->assertDontSee('[[ OWNER');
        $this->get("/en/{$privacy->slug_en}")->assertOk()->assertSee('Real text.');
    }
}
