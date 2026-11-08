<?php

namespace Tests\Feature\Admin;

use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\Project;
use App\Models\Service;
use App\Support\CannotPublish;
use Database\Seeders\CmsSeeder;
use Illuminate\Support\Facades\Storage;
use Tests\Support\AdminTestCase;

class CmsGapsTest extends AdminTestCase
{
    public function test_content_with_an_owner_placeholder_cannot_be_published(): void
    {
        $service = Service::create(['title' => ['nl' => 'A', 'en' => 'A'], 'summary' => ['nl' => 'x [[ OWNER: prijs ]]', 'en' => 'y']]);

        try {
            $service->publish();
            $this->fail('should be blocked');
        } catch (CannotPublish $e) {
            $this->assertStringContainsString('[[ OWNER', implode(' ', $e->issues));
        }

        $service->update(['summary' => ['nl' => 'x', 'en' => 'y']]);
        $service->fresh()->publish();
        $this->assertTrue($service->fresh()->isLive());
    }

    public function test_a_placeholder_added_in_a_draft_blocks_publishing_the_changes(): void
    {
        $service = Service::create(['title' => ['nl' => 'A', 'en' => 'A'], 'summary' => ['nl' => 'x', 'en' => 'y']]);
        $service->publish();
        $service->fresh()->saveFromForm(['summary' => ['nl' => 'x', 'en' => '[[ OWNER: tekst ]]']]);

        $this->expectException(CannotPublish::class);
        $service->fresh()->publish();
    }

    public function test_the_seeded_legal_pages_stay_drafts_until_the_owner_fills_them_in(): void
    {
        $this->seed(CmsSeeder::class);

        foreach (['privacy', 'terms'] as $key) {
            $page = Page::where('key', $key)->firstOrFail();
            $this->assertSame('draft', $page->workflowState());

            try {
                $page->publish();
                $this->fail("$key must not publish with placeholders");
            } catch (CannotPublish $e) {
                $this->assertStringContainsString('[[ OWNER', implode(' ', $e->issues));
            }
        }
    }

    public function test_a_project_needs_only_a_title_and_a_category_to_publish(): void
    {
        $project = Project::create(['title' => ['nl' => 'ServerScope', 'en' => 'ServerScope'], 'kind' => ['nl' => 'Servermonitoring-dashboard', 'en' => 'Server monitoring dashboard']]);

        $project->publish();

        $this->assertTrue($project->fresh()->isLive());
        $this->assertNull($project->fresh()->t('outcome', 'nl'), 'nothing is invented for missing fields');
    }

    public function test_serverscope_is_seeded_as_a_draft_with_only_brief_facts(): void
    {
        $this->seed(CmsSeeder::class);
        $p = Project::where('slug_en', 'serverscope')->firstOrFail();

        $this->assertSame('draft', $p->workflowState());
        $this->assertSame('Server monitoring dashboard', $p->t('kind', 'en'));
        $this->assertNull($p->year);
        $this->assertNull($p->url);
        $this->assertNull($p->t('brief', 'en'));
        $this->assertNull($p->t('outcome', 'en'));
        $this->assertTrue($p->is_featured);
    }

    public function test_public_visibility_uses_live_values_not_the_draft(): void
    {
        $service = Service::create(['title' => ['nl' => 'A', 'en' => 'A'], 'summary' => ['nl' => 'x', 'en' => 'y']]);
        $service->publish();
        $service->fresh()->saveFromForm(['title' => ['nl' => 'A', 'en' => '']]);
        $fresh = $service->fresh();

        $this->assertNotSame([], $fresh->translationGaps()['en'], 'publishing would be blocked');
        $this->assertTrue($fresh->isTranslatedIn('en'), 'but the live English version is still complete');
    }

    public function test_the_four_services_from_the_owner_are_seeded_as_drafts(): void
    {
        $this->seed(CmsSeeder::class);

        $titles = Service::orderBy('sort')->get()->map(fn ($s) => $s->t('title', 'en'))->all();
        $this->assertSame(['Website development', 'Custom web applications', 'E-commerce', 'Maintenance & hosting'], $titles);
        $this->assertSame(0, Service::query()->published()->count());
    }

    public function test_the_homepage_seed_is_complete_in_both_languages_and_the_hidden_about_teaser_does_not_block_it(): void
    {
        $this->seed(CmsSeeder::class);
        $home = Page::where('key', 'home')->firstOrFail();

        $types = collect($home->blocks)->pluck('type')->all();
        $this->assertContains('about_teaser', $types);
        $this->assertSame([], $home->publishIssues());
        $home->publish();
        $this->assertTrue($home->fresh()->isLive());
    }

    public function test_media_conversions_skip_widths_larger_than_the_original(): void
    {
        Storage::fake('public');
        config(['media-library.queue_conversions_after_database_commit' => false]);
        $im = imagecreatetruecolor(1000, 600);
        $path = sys_get_temp_dir().'/small.jpg';
        imagejpeg($im, $path);

        $asset = MediaAsset::create(['title' => 'Klein']);
        $asset->addMedia($path)->toMediaCollection('file');
        $media = $asset->fresh()->file();

        $this->assertTrue($media->hasGeneratedConversion('webp-768'));
        $this->assertFalse($media->hasGeneratedConversion('webp-1200'), 'no upscaling');
        $this->assertFalse($media->hasGeneratedConversion('webp-2400'));
    }
}
