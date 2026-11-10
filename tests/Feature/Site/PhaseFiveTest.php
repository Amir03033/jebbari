<?php

namespace Tests\Feature\Site;

use App\Mail\ConfirmSubscription;
use App\Models\Page;
use App\Models\PageView;
use App\Models\Post;
use App\Models\Project;
use App\Models\Subscriber;
use App\Support\Settings;
use App\Support\Toc;
use Database\Seeders\PersonalBrandSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\Support\PublishesSite;
use Tests\TestCase;

class PhaseFiveTest extends TestCase
{
    use PublishesSite, RefreshDatabase;

    private const BROWSER = ['User-Agent' => 'Mozilla/5.0 (Macintosh) Safari/605'];

    private function page(string $key, array $title, array $blocks): Page
    {
        $page = Page::create(['key' => $key, 'kind' => 'system', 'title' => $title, 'blocks' => $blocks]);
        $page->publish();

        return $page;
    }

    private function project(array $attributes = []): Project
    {
        $project = Project::create(['title' => ['nl' => 'Demo', 'en' => 'Demo'], 'kind' => ['nl' => 'Soort', 'en' => 'Kind'], ...$attributes]);
        $project->publish();

        return $project;
    }

    public function test_the_new_pages_have_translated_addresses_and_stay_hidden_until_published(): void
    {
        $this->publishSite();
        $this->get('/nl/nu')->assertNotFound();

        $this->page('now', ['nl' => 'Nu', 'en' => 'Now'], [
            ['type' => 'focus', 'data' => ['visible' => true, 'label' => ['nl' => 'Bezig', 'en' => 'Focus'], 'items' => [['name' => ['nl' => 'Bouwen', 'en' => 'Building'], 'description' => ['nl' => 'Iets', 'en' => 'Something']]]]],
        ]);
        $this->page('uses', ['nl' => 'Stack', 'en' => 'Uses'], [
            ['type' => 'gear', 'data' => ['visible' => true, 'label' => ['nl' => 'Een', 'en' => 'One'], 'items' => [['name' => ['nl' => 'A', 'en' => 'A'], 'description' => ['nl' => 'B', 'en' => 'B']]]]],
            ['type' => 'gear', 'data' => ['visible' => true, 'label' => ['nl' => 'Twee', 'en' => 'Two'], 'items' => [['name' => ['nl' => 'C', 'en' => 'C'], 'description' => ['nl' => 'D', 'en' => 'D']]]]],
        ]);

        $this->get('/nl/nu')->assertOk()->assertSee('Bouwen');
        $this->get('/en/now')->assertOk()->assertSee('Building');
        $this->get('/en/uses')->assertOk()->assertSee('One')->assertSee('Two');
        $this->get('/nl/stack')->assertOk();
        // The footer links to them once they are live.
        $this->get('/nl')->assertSee('href="'.url('/nl/nu').'"', false);
    }

    public function test_the_cv_is_assembled_from_the_about_page(): void
    {
        $this->publishSite();
        Page::where('key', 'about')->first()->forceFill(['blocks' => [
            ['type' => 'experience', 'data' => ['visible' => true, 'label' => ['nl' => 'Ervaring', 'en' => 'Experience'], 'items' => [['period' => '2020', 'role' => ['nl' => 'Bouwer', 'en' => 'Builder'], 'description' => ['nl' => 'Werk', 'en' => 'Work']]]]],
        ]])->save();
        $this->page('cv', ['nl' => 'Cv', 'en' => 'CV'], []);

        $this->get('/en/cv')->assertOk()->assertSee('Builder')->assertSee('data-print', false);
    }

    public function test_questions_render_as_an_accordion_with_structured_data(): void
    {
        $this->publishSite();
        $home = Page::where('key', 'home')->first();
        $home->forceFill(['blocks' => [...$home->blocks, ['type' => 'faq', 'data' => ['visible' => true, 'label' => ['nl' => 'Vragen', 'en' => 'Questions'],
            'items' => [['question' => ['nl' => 'Waarom?', 'en' => 'Why?'], 'answer' => ['nl' => 'Daarom.', 'en' => 'Because.']]]]]]])->save();

        $html = $this->get('/en')->assertOk()->assertSee('Why?')->assertSee('Because.')->getContent();
        $this->assertStringContainsString('<details class="faq-item"', $html);
        $this->assertStringContainsString('"@type":"FAQPage"', $html);
    }

    public function test_a_case_study_shows_timeline_changelog_and_demo_and_allows_only_that_demo_host_in_a_frame(): void
    {
        $this->publishSite();
        $this->assertStringNotContainsString('frame-src', $this->get('/en')->headers->get('Content-Security-Policy'));

        $project = $this->project([
            'timeline' => [['date' => 'Jan 2026', 'title' => ['nl' => 'Start', 'en' => 'Kick-off'], 'text' => ['nl' => '', 'en' => '']]],
            'changelog' => [['version' => '1.1', 'date' => 'Feb 2026', 'notes' => ['nl' => "Een\nTwee", 'en' => "One\nTwo"]]],
            'demo_url' => 'https://demo.example.test/app',
        ]);

        $response = $this->get('/en/work/'.$project->slug_en)->assertOk();
        $response->assertSee('Kick-off')->assertSee('1.1')->assertSee('<li>Two</li>', false)->assertSee('data-demo="https://demo.example.test/app"', false);
        // Nothing is embedded until the visitor asks; the policy names that one host and nothing else.
        $response->assertDontSee('<iframe', false);
        $this->assertStringContainsString('frame-src https://demo.example.test', $response->headers->get('Content-Security-Policy'));
    }

    public function test_the_work_page_offers_a_filter_only_when_there_is_something_to_filter(): void
    {
        $this->publishSite();
        foreach (['A', 'B', 'C'] as $name) {
            $this->project(['title' => ['nl' => $name, 'en' => $name], 'stack' => ['Laravel']]);
        }
        $this->get('/en/work')->assertDontSee('data-filter=', false);

        $this->project(['title' => ['nl' => 'D', 'en' => 'D'], 'stack' => ['Laravel', 'Vue']]);
        $this->get('/en/work')->assertSee('data-filter="laravel"', false)->assertSee('data-tags="laravel|vue"', false)->assertDontSee('data-filter="vue"', false);
    }

    public function test_an_article_gets_a_table_of_contents_and_the_blog_a_feed(): void
    {
        $this->publishSite();
        $toc = Toc::build('<h2>One</h2><p>x</p><h2>One</h2>');
        $this->assertSame(['one', 'one-2'], array_column($toc['items'], 'id'));

        $post = Post::create(['title' => ['nl' => 'Stuk', 'en' => 'Piece'], 'excerpt' => ['nl' => 'Kort', 'en' => 'Short'], 'body' => ['nl' => '<h2>Een</h2><p>a</p><h2>Twee</h2>', 'en' => '<h2>First</h2><p>a</p><h2>Second</h2>'], 'published_at' => now()->subDay()]);
        $post->publish();

        $this->get('/en/blog/'.$post->slug_en)->assertOk()->assertSee('href="#second"', false)->assertSee('data-toc', false)->assertSee('application/rss+xml', false);

        $feed = $this->get('/en/blog/feed.xml')->assertOk();
        $this->assertStringContainsString('application/rss+xml', $feed->headers->get('Content-Type'));
        $feed->assertSee('<title>Piece</title>', false)->assertSee(url('/en/blog/'.$post->slug_en), false);
    }

    public function test_share_images_are_generated_for_projects(): void
    {
        if (! function_exists('imagettftext')) {
            $this->markTestSkipped('GD with FreeType is not installed.');
        }

        $this->publishSite();
        $project = $this->project(['title' => ['nl' => 'Een lang projectnaam voor de kaart', 'en' => 'A long project name for the card']]);

        $this->get('/en/work/'.$project->slug_en)->assertSee('property="og:image" content="'.url('/share/en/work/'.$project->slug_en.'.png').'"', false);
        $image = $this->get('/share/en/work/'.$project->slug_en.'.png')->assertOk();
        $this->assertSame('image/png', $image->headers->get('Content-Type'));
        $this->get('/share/en/work/nope.png')->assertNotFound();
    }

    public function test_the_command_menu_lists_live_content_only(): void
    {
        $this->publishSite();
        $this->project(['title' => ['nl' => 'Zichtbaar ding', 'en' => 'Shown thing']]);
        Project::create(['title' => ['nl' => 'Geheim ding', 'en' => 'Secret thing'], 'kind' => ['nl' => 'x', 'en' => 'x']]);

        $this->get('/en')->assertSee('id="command-menu"', false)->assertSee('data-cmd-open', false)->assertSee('Shown thing')->assertDontSee('Secret thing');
    }

    public function test_github_repositories_come_from_the_server_and_failures_stay_quiet(): void
    {
        $this->publishSite();
        $about = Page::where('key', 'about')->first();
        $about->forceFill(['blocks' => [['type' => 'github', 'data' => ['visible' => true]]]])->save();

        Http::fake(['api.github.com/*' => Http::sequence()
            ->push([
                ['name' => 'tool', 'description' => 'A tool', 'language' => 'PHP', 'stargazers_count' => 4, 'html_url' => 'https://github.com/someone/tool', 'pushed_at' => '2026-01-01T00:00:00Z', 'fork' => false],
                ['name' => 'copy', 'html_url' => 'https://github.com/someone/copy', 'fork' => true],
            ])
            ->push('nope', 500)]);

        // No account set: nothing is requested and nothing is shown.
        $this->get('/en/about')->assertOk()->assertDontSee('repo-grid', false);
        Http::assertNothingSent();

        Settings::save('social', ['github_username' => 'someone']);
        $this->get('/en/about')->assertOk()->assertSee('https://github.com/someone/tool', false)->assertDontSee('someone/copy', false);

        Cache::flush();
        Settings::save('social', ['github_username' => 'someone']);
        $this->get('/en/about')->assertOk()->assertDontSee('repo-grid', false);
    }

    public function test_newsletter_sign_up_is_double_opt_in(): void
    {
        Mail::fake();
        $this->publishSite();

        $this->from('/nl/blog')->post('/nl/newsletter', ['newsletter_email' => 'geen-adres'])->assertSessionHasErrorsIn('newsletter', 'newsletter_email');
        $this->from('/nl/blog')->post('/nl/newsletter', ['newsletter_email' => 'bot@example.test', 'website' => 'spam'])->assertRedirect();
        $this->assertSame(0, Subscriber::count());

        $this->from('/nl/blog')->post('/nl/newsletter', ['newsletter_email' => 'Lezer@Example.test'])->assertRedirect(url('/nl/blog').'#newsletter');
        $subscriber = Subscriber::firstOrFail();
        $this->assertSame('lezer@example.test', $subscriber->email);
        $this->assertSame(0, Subscriber::active()->count());
        Mail::assertSent(ConfirmSubscription::class, fn ($mail) => $mail->hasTo('lezer@example.test'));

        // The links only work when signed.
        $this->get('/newsletter/confirm/'.$subscriber->id)->assertForbidden();
        $this->get(URL::signedRoute('newsletter.confirm', ['subscriber' => $subscriber->id]))->assertOk()->assertSee('Je bent aangemeld');
        $this->assertSame(1, Subscriber::active()->count());

        $this->get(URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $subscriber->id]))->assertOk()->assertSee('Je bent afgemeld');
        $this->assertSame(0, Subscriber::active()->count());
    }

    public function test_page_views_are_counted_without_cookies_and_skip_bots_and_do_not_track(): void
    {
        $this->publishSite();

        $this->get('/nl', self::BROWSER)->assertOk();
        $this->get('/nl', self::BROWSER)->assertOk();
        $this->get('/nl/werk', self::BROWSER)->assertOk();
        $this->get('/nl', ['User-Agent' => 'Googlebot/2.1'])->assertOk();
        $this->get('/nl', self::BROWSER + ['DNT' => '1'])->assertOk();
        $this->get('/nl/bestaat-niet', self::BROWSER)->assertNotFound();

        $this->assertSame(3, PageView::totalSince(1));
        $this->assertSame('/nl', PageView::top()->first()->path);
        // Only a date, a path and a number are stored.
        $this->assertSame(['id', 'date', 'path', 'views'], array_keys(PageView::first()->getAttributes()));
    }

    public function test_proposed_content_never_goes_live_by_itself(): void
    {
        $this->publishSite();
        $live = $this->get('/nl')->getContent();

        PersonalBrandSeeder::$replace = true;
        $this->seed(PersonalBrandSeeder::class);

        // Published pages are unchanged for visitors; the proposal waits as a draft.
        $this->assertStringContainsString('Software', $this->get('/nl')->getContent());
        $this->get('/nl')->assertDontSee('Maken wat hierna komt');
        $home = Page::where('key', 'home')->first();
        $this->assertTrue($home->hasDraftChanges());
        $this->assertStringContainsString('Maken wat hierna komt', json_encode($home->withDraft()->blocks, JSON_UNESCAPED_UNICODE));

        // New pages are drafts, and owner placeholders block publishing.
        $this->get('/nl/nu')->assertNotFound();
        $this->assertNotEmpty(Page::where('key', 'now')->first()->publishIssues());
        $this->assertEmpty(Page::where('key', 'cv')->first()->publishIssues());

        // Running it again changes nothing more.
        $this->seed(PersonalBrandSeeder::class);
        $this->assertSame(9, Page::where('kind', 'system')->count());
        $this->assertSame(1, Project::where('title', 'like', '%ChinaSupply%')->count());
        $this->assertNotEmpty($live);
    }
}
