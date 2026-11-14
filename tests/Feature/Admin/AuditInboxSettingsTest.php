<?php

namespace Tests\Feature\Admin;

use App\Enums\InquiryStatus;
use App\Filament\Pages\Settings\CompanySettings;
use App\Filament\Resources\Inquiries\InquiryResource;
use App\Filament\Resources\Inquiries\Pages\ViewInquiry;
use App\Filament\Resources\Inquiries\RelationManagers\NotesRelationManager;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Models\Inquiry;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Service;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\CmsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Auth\Events\Login;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\Support\AdminTestCase;

class AuditInboxSettingsTest extends AdminTestCase
{
    // ------------------------------------------------------------------ audit

    public function test_changes_are_logged_with_the_user_and_the_old_and_new_values(): void
    {
        $this->actingAs($owner = $this->owner());
        $service = Service::create(['title' => ['nl' => 'Een', 'en' => 'One'], 'summary' => ['nl' => 'a', 'en' => 'b']]);
        $service->update(['sort' => 20]);

        $created = Activity::where('subject_type', Service::class)->where('event', 'created')->first();
        $updated = Activity::where('subject_type', Service::class)->where('event', 'updated')->first();

        $this->assertSame($owner->id, $created->causer_id);
        $this->assertSame($owner->id, $updated->causer_id);
        $this->assertEquals(20, $updated->attribute_changes['attributes']['sort'] ?? $updated->properties['attributes']['sort'] ?? null);
    }

    public function test_user_secrets_never_reach_the_audit_log(): void
    {
        $user = $this->owner();
        $user->update(['password' => 'een-geheim-wachtwoord-1', 'app_authentication_secret' => 'GEHEIM', 'name' => 'Nieuwe naam']);

        $dump = json_encode(Activity::where('subject_type', User::class)->get()->toArray());

        $this->assertStringContainsString('Nieuwe naam', $dump);
        $this->assertStringNotContainsString('een-geheim', $dump);
        $this->assertStringNotContainsString('GEHEIM', $dump);
        $this->assertStringNotContainsString('JBSWY3DPEHPK3PXP', $dump);
        $this->assertStringNotContainsString('$2y$', $dump, 'no password hashes');
    }

    public function test_the_audit_log_is_read_only_and_owner_only(): void
    {
        $owner = $this->owner();
        $service = Service::create(['title' => ['nl' => 'x', 'en' => 'y']]);
        $log = Activity::where('subject_id', $service->id)->firstOrFail();

        $this->assertTrue($owner->can('view', $log));
        $this->assertFalse($owner->can('update', $log));
        $this->assertFalse($owner->can('delete', $log));
        $this->assertFalse($owner->can('create', Activity::class));
        $this->assertFalse($this->editor()->can('viewAny', Activity::class));
    }

    public function test_successful_sign_in_is_logged_and_recorded(): void
    {
        $user = $this->owner();
        event(new Login('web', $user, false));

        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertTrue(Activity::where('event', 'login')->where('subject_id', $user->id)->exists());
    }

    // ------------------------------------------------------------------ inbox

    private function inquiry(array $o = []): Inquiry
    {
        return Inquiry::create(['name' => 'Jan', 'email' => 'jan@example.com', 'message' => 'Ik wil een boekingssysteem.', ...$o]);
    }

    public function test_new_inquiries_are_unread_and_counted(): void
    {
        $this->inquiry();
        $this->inquiry(['email' => 'b@example.com']);

        $this->assertSame(2, Inquiry::whereNull('read_at')->count());
        $this->assertSame('2', InquiryResource::getNavigationBadge());
        $this->assertSame(InquiryStatus::New, Inquiry::first()->status);
    }

    public function test_opening_an_inquiry_marks_it_read(): void
    {
        $inquiry = $this->inquiry();
        $this->actingAs($this->owner());

        Livewire::test(ViewInquiry::class, ['record' => $inquiry->getKey()]);

        $this->assertNotNull($inquiry->fresh()->read_at);
        $this->assertSame(InquiryStatus::InProgress, $inquiry->fresh()->status);
        $this->assertNull(InquiryResource::getNavigationBadge());
    }

    public function test_inquiries_can_be_marked_completed_reopened_unread_and_spam(): void
    {
        $inquiry = $this->inquiry();
        $this->actingAs($this->owner());

        Livewire::test(ViewInquiry::class, ['record' => $inquiry->getKey()])->callAction('complete');
        $this->assertSame(InquiryStatus::Completed, $inquiry->fresh()->status);
        $this->assertNotNull($inquiry->fresh()->completed_at);

        Livewire::test(ViewInquiry::class, ['record' => $inquiry->getKey()])->callAction('reopen');
        $this->assertSame(InquiryStatus::InProgress, $inquiry->fresh()->status);
        $this->assertNull($inquiry->fresh()->completed_at);

        Livewire::test(ViewInquiry::class, ['record' => $inquiry->getKey()])->callAction('spam');
        $this->assertSame(InquiryStatus::Spam, $inquiry->fresh()->status);

        $inquiry->fresh()->update(['read_at' => now()]);
        Livewire::test(ViewInquiry::class, ['record' => $inquiry->getKey()])->callAction('unread');
        $this->assertNull($inquiry->fresh()->read_at);
    }

    public function test_internal_notes_keep_their_author_and_history(): void
    {
        $inquiry = $this->inquiry();
        $this->actingAs($owner = $this->owner());

        Livewire::test(NotesRelationManager::class, ['ownerRecord' => $inquiry, 'pageClass' => ViewInquiry::class])
            ->callAction(TestAction::make('create')->table(), ['body' => 'Gebeld, terugbellen vrijdag.'])
            ->assertHasNoFormErrors();

        $note = $inquiry->notes()->first();
        $this->assertSame('Gebeld, terugbellen vrijdag.', $note->body);
        $this->assertSame($owner->id, $note->user_id);
    }

    public function test_every_role_can_work_the_inbox_but_only_the_owner_deletes(): void
    {
        $inquiry = $this->inquiry();

        foreach ([$this->owner(), $this->editor(), $this->support()] as $user) {
            $this->assertTrue($user->can('view', $inquiry));
            $this->assertTrue($user->can('update', $inquiry));
        }

        $this->assertTrue($this->owner()->can('delete', $inquiry));
        $this->assertFalse($this->editor()->can('delete', $inquiry));
        $this->assertFalse($this->support()->can('delete', $inquiry));
        $this->assertFalse($this->owner()->can('create', Inquiry::class), 'inquiries come only from the public form');
    }

    // --------------------------------------------------------------- settings

    public function test_the_owner_saves_company_details_and_it_is_audited(): void
    {
        $this->actingAs($this->owner());

        Livewire::test(CompanySettings::class)
            ->fillForm(['legal_name' => 'Jebbari B.V.', 'kvk' => '12345678'])
            ->call('save')->assertNotified('Opgeslagen');

        $this->assertSame('Jebbari B.V.', Settings::get('company.legal_name'));
        $this->assertSame('12345678', Settings::get('company.kvk'));
        $this->assertTrue(Activity::where('description', 'settings_updated')->exists());
    }

    public function test_editors_cannot_open_or_change_settings(): void
    {
        $this->actingAs($this->editor());

        $this->get('/admin/company-settings')->assertForbidden();
        $this->assertFalse(CompanySettings::canAccess());
    }

    public function test_settings_fall_back_to_a_default_when_unset(): void
    {
        $this->assertNull(Settings::get('company.kvk'));
        $this->assertSame('x', Settings::get('company.kvk', 'x'));
    }

    // ------------------------------------------------------------------- blog

    public function test_a_new_post_is_authored_by_the_signed_in_user_and_opens_for_editing(): void
    {
        $this->actingAs($owner = $this->owner());
        $category = PostCategory::create(['name' => ['nl' => 'Nieuws', 'en' => 'News']]);

        Livewire::test(CreatePost::class)
            ->fillForm(['title' => ['nl' => 'Hallo', 'en' => 'Hello'], 'category_id' => $category->id])
            ->call('create')->assertHasNoFormErrors();

        $post = Post::firstOrFail();
        $this->assertSame($owner->id, $post->author_id);
        $this->assertSame($category->id, $post->category_id);
        $this->assertSame('hallo', $post->slug_nl);
        $this->assertSame('hello', $post->slug_en);
        $this->assertSame('draft', $post->workflowState());
    }

    public function test_posts_carry_seo_metadata_in_both_languages(): void
    {
        $post = Post::create(['title' => ['nl' => 'T', 'en' => 'T'], 'seo' => ['title' => ['nl' => 'SEO nl', 'en' => 'SEO en'], 'description' => ['nl' => 'd', 'en' => 'd'], 'noindex' => true]]);

        $this->assertSame('SEO en', $post->fresh()->seo['title']['en']);
        $this->assertTrue($post->fresh()->seo['noindex']);
    }

    public function test_only_owners_and_editors_reach_the_blog_admin(): void
    {
        $this->actingAs($this->editor())->get('/admin/posts')->assertOk();
        $this->actingAs($this->support())->get('/admin/posts')->assertForbidden();
    }

    public function test_system_pages_cannot_be_created_or_deleted_by_editors_and_never_deleted_in_bulk(): void
    {
        $this->seed(CmsSeeder::class);
        $home = Page::where('key', 'home')->first();
        $privacy = Page::where('key', 'privacy')->first();

        $this->assertFalse($this->editor()->can('create', Page::class));
        $this->assertFalse($this->owner()->can('delete', $home), 'system pages are permanent');
        $this->assertTrue($this->owner()->can('delete', $privacy), 'legal pages can be removed by the owner');
        $this->assertFalse($this->owner()->can('deleteAny', Page::class));
    }
}
