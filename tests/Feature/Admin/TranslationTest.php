<?php

namespace Tests\Feature\Admin;

use App\Content\Blocks\Blocks;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\Page;
use App\Models\PostCategory;
use App\Models\Service;
use App\Support\HtmlSanitizer;
use Database\Seeders\CmsSeeder;
use Livewire\Livewire;
use Tests\Support\AdminTestCase;

class TranslationTest extends AdminTestCase
{
    public function test_values_are_read_per_language_without_silent_fallback(): void
    {
        $service = Service::create(['title' => ['nl' => 'Diensten', 'en' => null], 'summary' => ['nl' => 'a', 'en' => 'b']]);

        $this->assertSame('Diensten', $service->t('title', 'nl'));
        $this->assertNull($service->t('title', 'en'), 'no Dutch text may leak into English');

        app()->setLocale('en');
        $this->assertSame('b', $service->t('summary'));
    }

    public function test_gaps_are_reported_per_language(): void
    {
        $service = Service::create(['title' => ['nl' => 'Alleen NL'], 'summary' => ['nl' => 'x', 'en' => 'y']]);

        $this->assertSame(['nl' => [], 'en' => ['Titel']], $service->translationGaps());
        $this->assertFalse($service->isFullyTranslated());
    }

    public function test_html_only_text_counts_as_empty(): void
    {
        $service = Service::create(['title' => ['nl' => 'a', 'en' => '<p> </p>'], 'summary' => ['nl' => 'x', 'en' => 'y']]);

        $this->assertContains('Titel', $service->translationGaps()['en']);
    }

    public function test_page_block_gaps_name_the_block_and_field(): void
    {
        $this->seed(CmsSeeder::class);
        $home = Page::where('key', 'home')->first();
        $this->assertTrue($home->isFullyTranslated(), 'the seeded homepage is complete in both languages');

        $blocks = $home->blocks;
        $blocks[0]['data']['description']['en'] = '';
        $blocks[3]['data']['steps'][1]['title']['nl'] = '';
        $home->update(['blocks' => $blocks]);

        $gaps = $home->fresh()->translationGaps();
        $this->assertContains('Hero (opening): Beschrijving', $gaps['en']);
        $this->assertContains('Werkwijze (stappen): Titel van stap (2)', $gaps['nl']);
    }

    public function test_hidden_blocks_do_not_block_publishing(): void
    {
        $this->seed(CmsSeeder::class);
        $home = Page::where('key', 'home')->first();
        $blocks = $home->blocks;
        $blocks[0]['data']['description']['en'] = '';
        $blocks[0]['data']['visible'] = false;
        $home->update(['blocks' => $blocks]);

        $this->assertSame([], $home->fresh()->translationGaps()['en']);
    }

    public function test_slugs_are_generated_per_language_and_stay_unique(): void
    {
        $a = Service::create(['title' => ['nl' => 'Onderhoud & hosting', 'en' => 'Care & hosting'], 'summary' => ['nl' => 'x', 'en' => 'y']]);
        $b = Service::create(['title' => ['nl' => 'Onderhoud & hosting', 'en' => 'Care & hosting'], 'summary' => ['nl' => 'x', 'en' => 'y']]);

        $this->assertSame('onderhoud-hosting', $a->slug_nl);
        $this->assertSame('care-hosting', $a->slug_en);
        $this->assertSame('onderhoud-hosting-2', $b->slug_nl);
        $this->assertSame('care-hosting-2', $b->slug_en);
    }

    public function test_existing_slugs_are_never_rewritten_when_the_title_changes(): void
    {
        $service = Service::create(['title' => ['nl' => 'Eerste', 'en' => 'First'], 'summary' => ['nl' => 'x', 'en' => 'y']]);
        $service->update(['title' => ['nl' => 'Heel anders', 'en' => 'Totally different']]);

        $this->assertSame('eerste', $service->fresh()->slug_nl);
    }

    public function test_categories_get_slugs_from_their_name(): void
    {
        $category = PostCategory::create(['name' => ['nl' => 'Nieuws & updates', 'en' => 'News & updates']]);

        $this->assertSame('nieuws-updates', $category->slug_nl);
        $this->assertSame('news-updates', $category->slug_en);
    }

    public function test_rich_text_is_sanitised_when_saved(): void
    {
        $service = Service::create([
            'title' => ['nl' => 'a', 'en' => 'b'], 'summary' => ['nl' => 'a', 'en' => 'b'],
            'description' => [
                'nl' => '<p>Hallo <strong>wereld</strong></p><script>alert(1)</script><iframe src="https://evil.test"></iframe><a href="javascript:alert(1)">klik</a>',
                'en' => '<p onclick="x()" style="color:red">Hi <a href="https://example.com">link</a></p><img src=x onerror=alert(1)>',
            ],
        ]);

        $nl = $service->fresh()->t('description', 'nl');
        $en = $service->fresh()->t('description', 'en');

        $this->assertStringContainsString('<strong>wereld</strong>', $nl);
        $this->assertStringNotContainsString('script', $nl);
        $this->assertStringNotContainsString('iframe', $nl);
        $this->assertStringNotContainsString('javascript:', $nl);
        $this->assertStringNotContainsString('onclick', $en);
        $this->assertStringNotContainsString('style=', $en);
        $this->assertStringNotContainsString('<img', $en);
        $this->assertStringContainsString('rel="noopener noreferrer"', $en);
    }

    public function test_the_sanitiser_keeps_a_small_safe_subset(): void
    {
        $clean = HtmlSanitizer::clean('<h2>Kop</h2><ul><li>een</li></ul><blockquote>q</blockquote><a href="mailto:a@b.nl">m</a><table><tr><td>x</td></tr></table>');

        $this->assertStringContainsString('<h2>Kop</h2>', $clean);
        $this->assertStringContainsString('<ul><li>een</li></ul>', $clean);
        $this->assertStringContainsString('mailto:a&#64;b.nl', $clean);
        $this->assertStringNotContainsString('<table', $clean);
    }

    public function test_single_use_sections_cannot_be_added_twice(): void
    {
        $this->seed(CmsSeeder::class);
        $this->actingAs($this->owner());
        $home = Page::where('key', 'home')->first();

        $duplicate = $home->blocks;
        $duplicate[] = $duplicate[0];

        Livewire::test(EditPage::class, ['record' => $home->getKey()])
            ->fillForm(['blocks' => $duplicate])
            ->call('save')
            ->assertHasFormErrors(['blocks']);
    }

    public function test_pages_only_offer_their_own_block_types(): void
    {
        $home = Blocks::allowedFor('home');
        $about = Blocks::allowedFor('about');

        $this->assertArrayHasKey('hero', $home);
        $this->assertArrayNotHasKey('hero', $about);
        $this->assertArrayHasKey('biography', $about);
        $this->assertTrue($about['image']->singleton() === false, 'images may repeat');
        $this->assertNull(Blocks::find('home', 'biography'));
    }

    public function test_owner_can_edit_each_language_of_the_homepage_independently(): void
    {
        $this->seed(CmsSeeder::class);
        $this->actingAs($this->owner());
        $home = Page::where('key', 'home')->first();

        $blocks = $home->blocks;
        $blocks[0]['data']['headline']['en'] = "Software,\nfinished";

        Livewire::test(EditPage::class, ['record' => $home->getKey()])
            ->fillForm(['blocks' => $blocks])->call('save')->assertHasNoFormErrors();

        $draft = $home->fresh()->withDraft()->blocks[0]['data']['headline'];
        $this->assertSame("Software,\nfinished", $draft['en']);
        $this->assertSame("Software\ndie klopt", $draft['nl'], 'Dutch is untouched');
    }
}
