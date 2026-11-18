<?php

namespace Tests\Feature\Site;

use App\Filament\Pages\Settings\SiteStatus;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesUsers;
use Tests\Support\PublishesSite;
use Tests\TestCase;

class OfflineTest extends TestCase
{
    use CreatesUsers, PublishesSite, RefreshDatabase;

    public function test_the_site_is_online_by_default(): void
    {
        $this->publishSite();

        $this->get('/nl')->assertOk()->assertDontSee('offline-bar', false);
    }

    public function test_visitors_get_the_offline_page_with_503_and_retry_after(): void
    {
        $this->publishSite();
        Settings::save('offline', ['enabled' => true]);

        $this->get('/nl')->assertStatus(503)
            ->assertHeader('Retry-After', '3600')
            ->assertHeader('X-Robots-Tag', 'noindex')
            ->assertSee('Even niet bereikbaar')
            ->assertSee('De website is tijdelijk offline.');

        $this->get('/en/contact')->assertStatus(503)->assertSee('Not available right now');
        $this->get('/nl/werk/iets')->assertStatus(503);
    }

    public function test_the_owners_message_and_return_time_are_shown(): void
    {
        $this->publishSite();
        Settings::save('offline', [
            'enabled' => true,
            'message_nl' => 'We verhuizen naar een nieuwe server.',
            'back_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ]);

        $this->get('/nl')->assertStatus(503)->assertSee('We verhuizen naar een nieuwe server.')->assertSee('Verwacht terug:');
        // No English message: the default English text.
        $this->get('/en')->assertSee('The website is temporarily offline.');
    }

    public function test_the_admin_keeps_working_and_team_members_still_see_the_site(): void
    {
        $this->publishSite();
        Settings::save('offline', ['enabled' => true]);

        $this->get('/admin/login')->assertOk();
        $this->get('/up')->assertOk();

        $this->actingAs($this->owner())->get('/nl')->assertOk()->assertSee('offline-bar', false)->assertSee('Zet weer online');
    }

    public function test_the_owner_switches_the_site_offline_and_back_from_the_admin(): void
    {
        $this->publishSite();
        $this->actingAs($owner = $this->owner());

        Livewire::test(SiteStatus::class)
            ->set('data.message_nl', 'Even onderhoud.')
            ->callAction('goOffline');

        $this->assertTrue(Settings::get('offline.enabled'));
        $this->assertSame('Even onderhoud.', Settings::get('offline.message_nl'));
        auth()->logout();
        $this->get('/nl')->assertStatus(503)->assertSee('Even onderhoud.');

        $this->actingAs($owner);
        Livewire::test(SiteStatus::class)->callAction('goOnline');
        $this->assertFalse(Settings::get('offline.enabled'));
        auth()->logout();
        $this->get('/nl')->assertOk();
    }

    public function test_saving_the_message_keeps_the_switch(): void
    {
        $this->actingAs($this->owner());
        Settings::save('offline', ['enabled' => true]);

        Livewire::test(SiteStatus::class)->set('data.message_en', 'Back soon.')->call('save');

        $this->assertTrue(Settings::get('offline.enabled'));
        $this->assertSame('Back soon.', Settings::get('offline.message_en'));
    }

    public function test_only_the_owner_can_open_the_switch(): void
    {
        $this->actingAs($this->editor())->get('/admin/site-status')->assertForbidden();
        $this->actingAs($this->owner())->get('/admin/site-status')->assertOk();
    }
}
