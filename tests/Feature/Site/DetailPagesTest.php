<?php

namespace Tests\Feature\Site;

use App\Models\Page;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsSiteContent;
use Tests\Support\PublishesSite;
use Tests\TestCase;

class DetailPagesTest extends TestCase
{
    use BuildsSiteContent, PublishesSite, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publishSite();
    }

    // ------------------------------------------------------------- projects

    public function test_a_project_detail_page_shows_its_cms_content(): void
    {
        $asset = $this->makeImage('Cover', alt: ['nl' => 'Het dashboard', 'en' => 'The dashboard']);
        $project = $this->makeProject([
            'title' => ['nl' => 'Monitor', 'en' => 'Monitor'], 'kind' => ['nl' => 'Dashboard', 'en' => 'Dashboard'],
            'summary' => ['nl' => 'Korte omschrijving.', 'en' => 'Short description.'],
            'brief' => ['nl' => 'De opdracht was X.', 'en' => 'The brief was X.'],
            'built' => ['nl' => '<p>Dit is gebouwd.</p>', 'en' => '<p>This was built.</p>'],
            'outcome' => ['nl' => 'Het werkt.', 'en' => 'It works.'],
            'year' => 2026, 'client_name' => 'Voorbeeldklant', 'stack' => ['Laravel', 'Alpine'], 'url' => 'https://monitor.example.test',
            'cover_media_id' => $asset->id, 'gallery' => [['media_id' => $asset->id, 'caption' => ['nl' => 'Bijschrift', 'en' => 'Caption']]],
        ]);

        $nl = $this->get("/nl/werk/{$project->slug_nl}")->assertOk();
        $nl->assertSee('Monitor')->assertSee('Korte omschrijving.')->assertSee('De opdracht was X.')->assertSee('Dit is gebouwd.')->assertSee('Het werkt.')
            ->assertSee('2026')->assertSee('Voorbeeldklant')->assertSee('Laravel')->assertSee('Alpine')->assertSee('monitor.example.test')
            ->assertSee('Bijschrift')->assertSee('alt="Het dashboard"', false);

        $this->get("/en/work/{$project->slug_en}")->assertSee('The brief was X.')->assertSee('alt="The dashboard"', false)->assertSee('Caption');
    }

    public function test_a_case_study_shows_objectives_features_and_the_next_project(): void
    {
        $main = $this->makeProject([
            'title' => ['nl' => 'Hoofd', 'en' => 'Main'], 'published_at' => now()->subDay(),
            'objectives' => ['nl' => "Sneller laden\nEenvoudig beheren", 'en' => "Load faster\nEasy to manage"],
            'features' => ['nl' => "Live statuskaart\nMeldingen per e-mail", 'en' => "Live status map\nEmail alerts"],
        ]);
        $this->makeProject(['title' => ['nl' => 'Volgend', 'en' => 'Following'], 'published_at' => now()->subDays(2)]);

        $nl = $this->get("/nl/werk/{$main->slug_nl}")->assertOk();
        $nl->assertSee('Sneller laden')->assertSee('Eenvoudig beheren')->assertSee('Live statuskaart')->assertSee('Meldingen per e-mail')
            ->assertSee(__('site.project.objectives', [], 'nl'))->assertSee(__('site.project.features', [], 'nl'));
        $this->assertSame(1, substr_count($nl->getContent(), 'class="next-panel'), 'one closing next-project panel');

        $this->get("/en/work/{$main->slug_en}")->assertSee('Load faster')->assertSee('Email alerts')->assertSee(__('site.project.objectives', [], 'en'));
    }

    public function test_case_study_sections_that_have_no_content_are_left_out(): void
    {
        $project = $this->makeProject(['objectives' => null, 'features' => null, 'outcome' => null, 'built' => null, 'gallery' => []]);

        $html = $this->get("/nl/werk/{$project->slug_nl}")->assertOk()->getContent();

        foreach (['objectives', 'features', 'outcome', 'interface'] as $key) {
            $this->assertStringNotContainsString(__("site.project.$key", [], 'nl'), $html, "$key heading is hidden when empty");
        }
    }

    public function test_project_pages_list_related_work_by_shared_technology(): void
    {
        $main = $this->makeProject(['title' => ['nl' => 'Hoofd', 'en' => 'Main'], 'stack' => ['Laravel', 'Vue']]);
        $this->makeProject(['title' => ['nl' => 'Verwant', 'en' => 'Related'], 'stack' => ['Laravel']]);
        $this->makeProject(['title' => ['nl' => 'Concept', 'en' => 'Draft'], 'stack' => ['Laravel']], publish: false);

        $this->get("/nl/werk/{$main->slug_nl}")->assertSee('Gerelateerd werk')->assertSee('Verwant')->assertDontSee('Concept')
            ->assertDontSee('href="'.url("/nl/werk/{$main->slug_nl}").'" class="plate', false);
    }

    public function test_the_work_index_lists_published_projects_and_has_a_fallback_when_empty(): void
    {
        $this->get('/nl/werk')->assertOk()->assertSee('Er zijn nog geen projecten om te tonen.');

        $this->makeProject();
        $this->get('/nl/werk')->assertSee('Testproject')->assertDontSee('Er zijn nog geen projecten');
    }

    public function test_a_project_without_an_image_gets_a_typographic_tile_not_a_fake_screenshot(): void
    {
        $this->makeProject();

        $html = $this->get('/nl/werk')->getContent();

        $this->assertStringContainsString('data-tile', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    // ------------------------------------------------------------- services

    public function test_a_service_detail_page_shows_benefits_deliverables_timeline_and_packages(): void
    {
        $service = $this->makeService([
            'description' => ['nl' => '<p>Uitleg over de dienst.</p>', 'en' => '<p>About the service.</p>'],
            'benefits' => ['nl' => "Snel\nVeilig", 'en' => "Fast\nSecure"],
            'included' => ['nl' => "Ontwerp\nBouw", 'en' => "Design\nBuild"],
            'not_included' => ['nl' => 'Fotografie', 'en' => 'Photography'],
            'timeline' => ['nl' => 'Zes weken', 'en' => 'Six weeks'], 'tags' => ['Laravel'],
            'packages' => [
                ['name' => ['nl' => 'Basis', 'en' => 'Basic'], 'scope' => ['nl' => "Een pagina\nEen formulier", 'en' => "One page\nOne form"], 'price_from_eur' => 2500, 'price_note' => ['nl' => 'Excl. btw', 'en' => 'Excl. VAT'], 'visible' => true],
                ['name' => ['nl' => 'Maatwerk', 'en' => 'Custom'], 'scope' => ['nl' => 'Alles', 'en' => 'Everything'], 'visible' => true],
                ['name' => ['nl' => 'Verborgen', 'en' => 'Hidden'], 'scope' => ['nl' => 'x', 'en' => 'x'], 'price_from_eur' => 1, 'visible' => false],
            ],
        ]);

        $nl = $this->get("/nl/diensten/{$service->slug_nl}")->assertOk();
        $nl->assertSee('Uitleg over de dienst.')->assertSee('Snel')->assertSee('Veilig')->assertSee('Ontwerp')->assertSee('Fotografie')->assertSee('Zes weken')
            ->assertSee('Laravel')->assertSee('Basis')->assertSee('€ 2.500')->assertSee('Vanaf')->assertSee('Excl. btw')
            ->assertSee('Maatwerk')->assertSee('Op aanvraag')->assertDontSee('Verborgen');

        $this->get("/en/services/{$service->slug_en}")->assertSee('Fast')->assertSee('€ 2.500')->assertSee('From')->assertSee('On request');
    }

    public function test_the_services_page_lists_services_and_a_packages_table_with_prices_only_where_entered(): void
    {
        $this->makeService(['packages' => [['name' => ['nl' => 'Basis', 'en' => 'Basic'], 'scope' => ['nl' => 'x', 'en' => 'x'], 'price_from_eur' => 3000, 'visible' => true]]]);

        $this->get('/nl/diensten')->assertOk()->assertSee('Webbouw')->assertSee('Pakketten')->assertSee('€ 3.000');
    }

    public function test_the_services_page_omits_the_packages_table_when_there_are_none(): void
    {
        $this->makeService();

        $this->get('/nl/diensten')->assertOk()->assertDontSee('Pakketten');
    }

    public function test_service_pages_link_to_other_services_and_the_contact_page(): void
    {
        $a = $this->makeService(['title' => ['nl' => 'Alpha', 'en' => 'Alpha']]);
        $this->makeService(['title' => ['nl' => 'Beta', 'en' => 'Beta']]);

        $this->get("/nl/diensten/{$a->slug_nl}")->assertSee('Andere diensten')->assertSee('Beta')->assertSee('href="'.url('/nl/contact').'"', false);
    }

    // ----------------------------------------------------------------- blog

    public function test_the_blog_index_lists_posts_newest_first_with_dates_and_categories(): void
    {
        $cat = $this->makeCategory();
        $this->makePost(['title' => ['nl' => 'Oud', 'en' => 'Old'], 'published_at' => now()->subDays(5), 'category_id' => $cat->id]);
        $this->makePost(['title' => ['nl' => 'Nieuw', 'en' => 'New'], 'published_at' => now()->subDay(), 'category_id' => $cat->id]);

        $html = $this->get('/nl/blog')->assertOk()->assertSee('Nieuws')->getContent();

        $this->assertLessThan(strpos($html, 'Oud'), strpos($html, 'Nieuw'));
        $this->assertStringContainsString('<time datetime="'.now()->subDay()->timezone('Europe/Amsterdam')->toDateString().'"', $html);
    }

    public function test_an_article_page_shows_the_body_date_reading_time_category_and_related_posts(): void
    {
        $cat = $this->makeCategory();
        $post = $this->makePost(['category_id' => $cat->id, 'body' => ['nl' => '<h2>Kop</h2><p>'.str_repeat('woord ', 450).'</p>', 'en' => '<h2>Head</h2><p>'.str_repeat('word ', 450).'</p>']]);
        $this->makePost(['title' => ['nl' => 'Tweede artikel', 'en' => 'Second article'], 'category_id' => $cat->id]);

        $this->get("/nl/blog/{$post->slug_nl}")->assertOk()->assertSee('<h2 id="kop">Kop</h2>', false)->assertSee('3 minuten lezen')
            ->assertSee('Nieuws')->assertSee('Lees verder')->assertSee('Tweede artikel');
        $this->get("/en/blog/{$post->slug_en}")->assertSee('<h2 id="head">Head</h2>', false)->assertSee('3 minute read');
    }

    public function test_article_text_is_sanitised_when_rendered(): void
    {
        $post = $this->makePost();
        $post->forceFill(['body' => ['nl' => '<p>Veilig</p><script>alert(1)</script><a href="javascript:x()">link</a>', 'en' => '<p>Safe</p>']])->saveQuietly();

        $html = $this->get("/nl/blog/{$post->slug_nl}")->assertOk()->getContent();

        $this->assertStringContainsString('Veilig', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_category_pages_show_only_their_own_posts(): void
    {
        $news = $this->makeCategory(['name' => ['nl' => 'Nieuws', 'en' => 'News']]);
        $tech = $this->makeCategory(['name' => ['nl' => 'Techniek', 'en' => 'Tech']]);
        $this->makePost(['title' => ['nl' => 'Nieuwsbericht', 'en' => 'News item'], 'category_id' => $news->id]);
        $this->makePost(['title' => ['nl' => 'Techbericht', 'en' => 'Tech item'], 'category_id' => $tech->id]);

        $this->assertStringContainsString('Nieuwsbericht', $nl = $this->body("/nl/blog/categorie/{$news->slug_nl}"));
        $this->assertStringNotContainsString('Techbericht', $nl);
        $this->assertStringContainsString('Tech item', $en = $this->body("/en/blog/category/{$tech->slug_en}"));
        $this->assertStringNotContainsString('News item', $en);
    }

    public function test_categories_without_visible_posts_are_not_offered(): void
    {
        $empty = $this->makeCategory(['name' => ['nl' => 'Leeg', 'en' => 'Empty']]);
        $this->makePost();

        $this->get('/nl/blog')->assertDontSee('Leeg');
    }

    public function test_the_blog_paginates(): void
    {
        foreach (range(1, 11) as $i) {
            $this->makePost(['title' => ['nl' => "Artikel $i", 'en' => "Article $i"], 'published_at' => now()->subMinutes($i)]);
        }

        $first = $this->body('/nl/blog');
        $this->assertStringContainsString('Artikel 1<', $first);
        $this->assertStringNotContainsString('Artikel 11<', $first);
        $this->assertStringContainsString('1 / 2', $first);
        $this->assertStringContainsString('rel="next"', $first);
        $this->get('/nl/blog?page=2')->assertSee('Artikel 11<', false)->assertSee('rel="prev"', false);
    }

    public function test_the_blog_shows_a_calm_message_when_empty(): void
    {
        $this->get('/nl/blog')->assertOk()->assertSee('Er zijn nog geen artikelen.');
    }

    // ----------------------------------------------------- language switching

    public function test_the_language_switch_maps_detail_pages_to_the_other_languages_slug(): void
    {
        $service = $this->makeService(['title' => ['nl' => 'Onderhoud', 'en' => 'Maintenance']]);
        $project = $this->makeProject(['title' => ['nl' => 'Dossier', 'en' => 'Folder']]);
        $post = $this->makePost(['title' => ['nl' => 'Nieuwsbrief', 'en' => 'Newsletter']]);

        $this->get("/nl/diensten/{$service->slug_nl}")->assertSee('href="'.url("/en/services/{$service->slug_en}").'" hreflang="en"', false);
        $this->get("/en/work/{$project->slug_en}")->assertSee('href="'.url("/nl/werk/{$project->slug_nl}").'" hreflang="nl"', false);
        $this->get("/nl/blog/{$post->slug_nl}")->assertSee('href="'.url("/en/blog/{$post->slug_en}").'" hreflang="en"', false);
    }

    public function test_the_language_switch_falls_back_to_the_section_index_when_the_other_language_is_unavailable(): void
    {
        $project = $this->makeProject();
        $project->forceFill(['title' => ['nl' => 'Testproject', 'en' => '']])->saveQuietly();

        $this->get("/nl/werk/{$project->slug_nl}")->assertSee('href="'.url('/en/work').'" hreflang="en"', false);
    }

    // -------------------------------------------------------------- about page

    public function test_the_about_page_renders_its_blocks_and_falls_back_to_the_page_title(): void
    {
        $this->get('/nl/over-mij')->assertOk()->assertSee('<h1', false)->assertSee('Over mij');

        $about = Page::where('key', 'about')->first();
        $about->update(['blocks' => [
            ['type' => 'intro', 'data' => ['visible' => true, 'label' => ['nl' => 'Over mij', 'en' => 'About'], 'heading' => ['nl' => 'Ik bouw dingen', 'en' => 'I build things'], 'body' => ['nl' => 'Intro.', 'en' => 'Intro.']]],
            ['type' => 'skills', 'data' => ['visible' => true, 'label' => ['nl' => 'Vaardigheden', 'en' => 'Skills'], 'items' => [['name' => ['nl' => 'PHP', 'en' => 'PHP'], 'description' => ['nl' => 'Sinds lang.', 'en' => 'For long.']]]]],
            ['type' => 'principles', 'data' => ['visible' => true, 'label' => ['nl' => 'Principes', 'en' => 'Principles'], 'items' => [['name' => ['nl' => 'Eerlijk', 'en' => 'Honest']]]]],
        ]]);
        $about->fresh()->publish();

        $this->get('/nl/over-mij')->assertSee('Ik bouw dingen')->assertSee('Vaardigheden')->assertSee('PHP')->assertSee('Eerlijk');
        $this->get('/en/about')->assertSee('I build things')->assertSee('Skills')->assertSee('Honest');
    }

    // ---------------------------------------------------------- images and a11y

    public function test_images_are_responsive_with_explicit_size_lazy_loading_and_localised_alt(): void
    {
        $asset = $this->makeImage('Cover', 1600, 1000, ['nl' => 'Nederlandse alt', 'en' => 'English alt']);
        $project = $this->makeProject(['cover_media_id' => $asset->id]);
        $other = $this->makeProject(['title' => ['nl' => 'Ander', 'en' => 'Other'], 'cover_media_id' => $asset->id]);

        $html = $this->get('/nl/werk')->getContent();

        $this->assertStringContainsString('<source type="image/webp"', $html);
        $this->assertMatchesRegularExpression('#srcset="[^"]*480w[^"]*768w[^"]*1200w[^"]*1600w#', $html);
        $this->assertStringNotContainsString('2400w', $html, 'no upscaled variant for a 1600px original');
        $this->assertStringContainsString('width="1600" height="1000"', $html);
        $this->assertStringContainsString('sizes="', $html);
        $this->assertStringContainsString('alt="Nederlandse alt"', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringContainsString('fetchpriority="high"', $html, 'the first image on the page is eager');
        $this->assertStringContainsString('alt="English alt"', $this->get('/en/work')->getContent());
    }

    public function test_decorative_images_get_an_empty_alt(): void
    {
        $asset = $this->makeImage('Sfeer', alt: null);
        $asset->update(['is_decorative' => true]);
        $project = $this->makeProject(['cover_media_id' => $asset->id]);

        $this->assertStringContainsString('alt=""', $this->get('/nl/werk')->getContent());
    }

    public function test_every_image_has_an_alt_attribute_and_dimensions(): void
    {
        $asset = $this->makeImage();
        $this->makeProject(['cover_media_id' => $asset->id, 'gallery' => [['media_id' => $asset->id, 'caption' => ['nl' => 'a', 'en' => 'b']]]]);
        $project = Project::latest('id')->first();

        $html = $this->get("/nl/werk/{$project->slug_nl}")->getContent();
        preg_match_all('/<img\b[^>]*>/', $html, $images);

        $this->assertNotEmpty($images[0]);
        foreach ($images[0] as $img) {
            $this->assertMatchesRegularExpression('/\balt="/', $img);
            $this->assertMatchesRegularExpression('/\bwidth="\d+"/', $img);
            $this->assertMatchesRegularExpression('/\bheight="\d+"/', $img);
        }
    }

    public function test_each_page_has_one_h1_landmarks_and_a_skip_link(): void
    {
        $service = $this->makeService();
        $project = $this->makeProject();
        $post = $this->makePost();

        foreach (['/nl', '/en', '/nl/werk', '/nl/diensten', '/nl/over-mij', '/nl/blog', '/nl/contact', "/nl/diensten/{$service->slug_nl}", "/nl/werk/{$project->slug_nl}", "/nl/blog/{$post->slug_nl}"] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertSame(1, preg_match_all('/<h1\b/', $html), "$url needs exactly one h1");
            $this->assertStringContainsString('<main id="main"', $html);
            $this->assertStringContainsString('href="#main"', $html);
            $this->assertStringContainsString('<nav aria-label=', $html);
            $this->assertStringContainsString('<meta name="viewport" content="width=device-width, initial-scale=1">', $html);
        }
    }
}
