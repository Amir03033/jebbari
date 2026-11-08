<?php

namespace Tests\Feature\Admin;

use App\Enums\PublishStatus;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Services\Pages\EditService;
use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Models\Service;
use App\Models\Testimonial;
use App\Support\CannotPublish;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\Support\AdminTestCase;

class PublishingTest extends AdminTestCase
{
    private function completeService(array $overrides = []): Service
    {
        return Service::create([
            'title' => ['nl' => 'Webapplicaties', 'en' => 'Web applications'],
            'summary' => ['nl' => 'Maatwerk.', 'en' => 'Custom.'],
            ...$overrides,
        ]);
    }

    /** One complete record per content type, used to prove the rules hold for all of them. */
    private function completeRecords(): array
    {
        return [
            'service' => $this->completeService(),
            'project' => Project::create(['title' => ['nl' => 'A', 'en' => 'A'], 'kind' => ['nl' => 'Web', 'en' => 'Web'], 'brief' => ['nl' => 'b', 'en' => 'b'], 'outcome' => ['nl' => 'o', 'en' => 'o']]),
            'post' => Post::create(['title' => ['nl' => 'T', 'en' => 'T'], 'excerpt' => ['nl' => 'e', 'en' => 'e'], 'body' => ['nl' => '<p>x</p>', 'en' => '<p>y</p>']]),
            'testimonial' => Testimonial::create(['name' => 'N', 'quote' => ['nl' => 'q', 'en' => 'q'], 'permission_confirmed' => true]),
            'page' => Page::create(['key' => 'privacy', 'kind' => 'legal', 'title' => ['nl' => 'Privacy', 'en' => 'Privacy'], 'blocks' => []]),
        ];
    }

    public function test_new_content_starts_as_an_invisible_draft_for_every_type(): void
    {
        foreach ($this->completeRecords() as $type => $record) {
            $this->assertSame(PublishStatus::Draft, $record->status, $type);
            $this->assertSame('draft', $record->workflowState(), $type);
            $this->assertFalse($record::query()->published()->whereKey($record->getKey())->exists(), "$type must not be live");
        }
    }

    public function test_publishing_makes_content_live_for_every_type(): void
    {
        foreach ($this->completeRecords() as $type => $record) {
            $record->publish();

            $this->assertTrue($record::query()->published()->whereKey($record->getKey())->exists(), "$type should be live");
            $this->assertSame('live', $record->fresh()->workflowState(), $type);
        }
    }

    public function test_editing_published_content_never_changes_the_live_version_for_every_type(): void
    {
        $changes = [
            'service' => ['title' => ['nl' => 'NIEUW', 'en' => 'NEW']],
            'project' => ['title' => ['nl' => 'NIEUW', 'en' => 'NEW']],
            'post' => ['title' => ['nl' => 'NIEUW', 'en' => 'NEW']],
            'testimonial' => ['quote' => ['nl' => 'NIEUW', 'en' => 'NEW']],
            'page' => ['title' => ['nl' => 'NIEUW', 'en' => 'NEW']],
        ];

        foreach ($this->completeRecords() as $type => $record) {
            $record->publish();
            $field = array_key_first($changes[$type]);
            $liveBefore = $record->fresh()->getAttribute($field);

            $record->fresh()->saveFromForm($changes[$type]);
            $fresh = $record->fresh();

            $this->assertSame($liveBefore, $fresh->getAttribute($field), "$type: live column must be untouched");
            $this->assertTrue($fresh->hasDraftChanges(), $type);
            $this->assertSame('live_changes', $fresh->workflowState(), $type);
            $this->assertSame('NIEUW', $fresh->withDraft()->t($field, 'nl'), "$type: the draft holds the edit");
            $this->assertSame($liveBefore['nl'], $fresh::query()->published()->find($fresh->getKey())->t($field, 'nl'), "$type: the public query still returns the live text");
        }
    }

    public function test_the_published_version_stays_live_until_publish_is_pressed(): void
    {
        $service = $this->completeService();
        $service->publish();
        $this->actingAs($this->owner());

        Livewire::test(EditService::class, ['record' => $service->getKey()])
            ->fillForm(['title' => ['nl' => 'Aangepast', 'en' => 'Changed']])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Webapplicaties', $service->fresh()->t('title', 'nl'));
        $this->assertSame('Aangepast', $service->fresh()->withDraft()->t('title', 'nl'));

        Livewire::test(EditService::class, ['record' => $service->getKey()])->callAction('publish');

        $this->assertSame('Aangepast', $service->fresh()->t('title', 'nl'));
        $this->assertFalse($service->fresh()->hasDraftChanges());
    }

    public function test_the_edit_form_shows_the_draft_not_the_live_text(): void
    {
        $service = $this->completeService();
        $service->publish();
        $service->fresh()->saveFromForm(['title' => ['nl' => 'In concept', 'en' => 'In draft']]);
        $this->actingAs($this->owner());

        Livewire::test(EditService::class, ['record' => $service->getKey()])
            ->assertFormSet(['title.nl' => 'In concept']);
    }

    public function test_discarding_a_draft_restores_the_live_form(): void
    {
        $service = $this->completeService();
        $service->publish();
        $service->fresh()->saveFromForm(['title' => ['nl' => 'Weg ermee', 'en' => 'Drop it']]);
        $this->actingAs($this->owner());

        Livewire::test(EditService::class, ['record' => $service->getKey()])->callAction('discard');

        $this->assertFalse($service->fresh()->hasDraftChanges());
        $this->assertSame('Webapplicaties', $service->fresh()->t('title', 'nl'));
    }

    public function test_saving_without_real_changes_leaves_no_draft(): void
    {
        $service = $this->completeService();
        $service->publish();

        $service->fresh()->saveFromForm(['title' => ['en' => 'Web applications', 'nl' => 'Webapplicaties'], 'summary' => ['nl' => 'Maatwerk.', 'en' => 'Custom.']]);

        $this->assertFalse($service->fresh()->hasDraftChanges());
    }

    public function test_unpublishing_takes_content_offline_but_keeps_it(): void
    {
        $service = $this->completeService();
        $service->publish();
        $service->fresh()->unpublish();

        $this->assertFalse(Service::query()->published()->whereKey($service->id)->exists());
        $this->assertNotNull(Service::find($service->id));
    }

    public function test_future_publication_dates_schedule_content(): void
    {
        $scheduled = $this->completeService(['published_at' => now()->addDay()]);
        $scheduled->publish();

        $this->assertSame('scheduled', $scheduled->fresh()->workflowState());
        $this->assertFalse(Service::query()->published()->whereKey($scheduled->id)->exists());

        $this->travel(2)->days();

        $this->assertTrue(Service::query()->published()->whereKey($scheduled->id)->exists());
    }

    public function test_publishing_is_blocked_until_both_languages_are_complete(): void
    {
        $service = Service::create(['title' => ['nl' => 'Alleen Nederlands'], 'summary' => ['nl' => 'Ja.']]);

        try {
            $service->publish();
            $this->fail('publishing should have been blocked');
        } catch (CannotPublish $e) {
            $this->assertContains('EN: Titel ontbreekt', $e->issues);
            $this->assertContains('EN: Samenvatting ontbreekt', $e->issues);
        }

        $this->assertSame(PublishStatus::Draft, $service->fresh()->status);
    }

    public function test_the_publish_button_refuses_and_explains_when_a_translation_is_missing(): void
    {
        $service = Service::create(['title' => ['nl' => 'Alleen Nederlands'], 'summary' => ['nl' => 'Ja.']]);
        $this->actingAs($this->owner());

        Livewire::test(EditService::class, ['record' => $service->getKey()])
            ->callAction('publish')
            ->assertNotified('Publiceren geblokkeerd');

        $this->assertSame(PublishStatus::Draft, $service->fresh()->status);
    }

    public function test_a_missing_translation_in_a_draft_edit_also_blocks_publishing_the_changes(): void
    {
        $service = $this->completeService();
        $service->publish();
        $service->fresh()->saveFromForm(['title' => ['nl' => 'Nieuw', 'en' => '']]);

        $this->expectException(CannotPublish::class);
        $service->fresh()->publish();
    }

    public function test_publish_actions_are_recorded_in_the_audit_log(): void
    {
        $service = $this->completeService();
        $this->actingAs($owner = $this->owner());

        $service->publish();
        $service->fresh()->saveFromForm(['title' => ['nl' => 'X', 'en' => 'Y']]);
        $service->fresh()->discardDraft();
        $service->fresh()->unpublish();

        foreach (['published', 'draft_saved', 'draft_discarded', 'unpublished'] as $event) {
            $log = Activity::query()->where('event', $event)->where('subject_id', $service->id)->first();
            $this->assertNotNull($log, "missing audit event $event");
            $this->assertSame($owner->id, $log->causer_id);
        }
    }

    public function test_previews_need_a_valid_signed_link_and_a_signed_in_editor(): void
    {
        $service = $this->completeService();
        $service->publish();
        $service->fresh()->saveFromForm(['title' => ['nl' => 'Nog niet live', 'en' => 'Not live yet']]);
        $service = $service->fresh();

        // No sign-in: sent to the login.
        $this->get($service->previewUrl())->assertRedirect('/admin/login');

        // Signed in but unsigned link.
        $this->actingAs($this->editor());
        $this->get('/preview/service/'.$service->id)->assertForbidden();

        // Valid link shows the DRAFT, marked, not indexable.
        $response = $this->get($service->previewUrl('nl'))->assertOk()
            ->assertSee('Nog niet live')
            ->assertSee('Gepubliceerd met niet-gepubliceerde wijzigingen');
        $this->assertStringContainsString('noindex', $response->headers->get('X-Robots-Tag'));
        $this->assertStringContainsString('noindex', $response->getContent());

        // English preview shows the English draft text.
        $this->get($service->previewUrl('en'))->assertOk()->assertSee('Not live yet');

        // The live public query is unaffected.
        $this->assertSame('Webapplicaties', Service::query()->published()->first()->t('title', 'nl'));
    }

    public function test_preview_links_expire(): void
    {
        $service = $this->completeService();
        $url = $service->previewUrl();
        $this->actingAs($this->editor());

        $this->travel(31)->minutes();

        $this->get($url)->assertForbidden();
    }

    public function test_roles_without_content_access_cannot_preview(): void
    {
        $service = $this->completeService();

        $this->actingAs($this->support())->get($service->previewUrl())->assertForbidden();
    }

    public function test_a_blog_post_goes_through_the_same_workflow(): void
    {
        $this->actingAs($this->owner());
        $post = Post::create(['title' => ['nl' => 'Eerste', 'en' => 'First'], 'excerpt' => ['nl' => 'e', 'en' => 'e'], 'body' => ['nl' => '<p>a</p>', 'en' => '<p>b</p>']]);

        Livewire::test(EditPost::class, ['record' => $post->getKey()])->callAction('publish')->assertNotified('Gepubliceerd');

        $post->refresh();
        $this->assertTrue($post->isLive());
        $this->assertNotNull($post->published_at);

        Livewire::test(EditPost::class, ['record' => $post->getKey()])
            ->fillForm(['title' => ['nl' => 'Eerste (herzien)', 'en' => 'First (revised)']])->call('save');

        $this->assertSame('Eerste', $post->fresh()->t('title', 'nl'));
        $this->assertTrue($post->fresh()->hasDraftChanges());
    }

    public function test_blog_posts_can_be_soft_deleted_and_restored_by_the_owner_only(): void
    {
        $post = Post::create(['title' => ['nl' => 'T', 'en' => 'T']]);

        $this->assertFalse($this->editor()->can('delete', $post));
        $this->assertTrue($this->owner()->can('delete', $post));

        $post->delete();
        $this->assertSoftDeleted($post);
        $post->restore();
        $this->assertNotSoftDeleted($post);
    }

    public function test_a_testimonial_cannot_go_live_without_confirmed_permission(): void
    {
        $t = Testimonial::create(['name' => 'Klant', 'quote' => ['nl' => 'Top', 'en' => 'Great'], 'permission_confirmed' => false]);

        try {
            $t->publish();
            $this->fail('should be blocked');
        } catch (CannotPublish $e) {
            $this->assertContains('Toestemming van de klant is niet bevestigd', $e->issues);
        }

        $t->saveFromForm(['permission_confirmed' => true]);
        $t->fresh()->publish();
        $this->assertTrue($t->fresh()->isLive());
    }

    public function test_project_publishing_requires_alt_text_on_images(): void
    {
        $project = $this->completeRecords()['project'];
        $asset = MediaAsset::create(['title' => 'Cover']);
        $project->update(['cover_media_id' => $asset->id]);

        $issues = $project->fresh()->publishIssues();
        $this->assertContains('Alt-tekst (NL) ontbreekt voor afbeelding "Cover"', $issues);
        $this->assertContains('Alt-tekst (EN) ontbreekt voor afbeelding "Cover"', $issues);

        $asset->update(['alt' => ['nl' => 'Een kapperszaak', 'en' => 'A barbershop']]);
        $this->assertSame([], $project->fresh()->publishIssues());

        $asset->update(['alt' => null, 'is_decorative' => true]);
        $this->assertSame([], $project->fresh()->publishIssues());
    }

    public function test_project_edit_page_saves_a_gallery_as_a_draft(): void
    {
        $project = $this->completeRecords()['project'];
        $project->publish();
        $asset = MediaAsset::create(['title' => 'Galerij', 'alt' => ['nl' => 'a', 'en' => 'a']]);
        $this->actingAs($this->owner());

        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm(['gallery' => [['media_id' => $asset->id, 'caption' => ['nl' => 'Voor', 'en' => 'Before']]]])
            ->call('save');

        $this->assertEmpty($project->fresh()->gallery, 'the live gallery must not change');
        $this->assertCount(1, $project->fresh()->withDraft()->gallery);
    }
}
