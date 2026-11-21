<?php

namespace Tests\Feature\Admin;

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Inquiry;
use App\Models\Lead;
use App\Models\PageView;
use App\Models\Proposal;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesUsers;
use Tests\Support\PublishesSite;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use CreatesUsers, PublishesSite, RefreshDatabase;

    public function test_it_greets_and_shows_what_needs_attention(): void
    {
        $this->publishSite();
        Inquiry::create(['name' => 'A', 'email' => 'a@example.test', 'message' => 'Hallo', 'locale' => 'nl']);
        Lead::create(['source' => 'wizard', 'name' => 'B', 'email' => 'b@example.test', 'submitted_at' => now()]);
        Proposal::create(['client_name' => 'C', 'client_email' => 'c@example.test', 'title' => 'X', 'sent_at' => now()->subWeek(), 'first_viewed_at' => now()->subDays(5)]);
        PageView::create(['date' => now(config('site.timezone'))->toDateString(), 'path' => '/nl', 'views' => 42]);
        $type = AppointmentType::query()->first() ?? AppointmentType::create(['name' => ['nl' => 'Kennismaking', 'en' => 'Intro'], 'duration_minutes' => 30, 'is_active' => true]);
        Appointment::create(['lead_id' => Lead::first()->id, 'appointment_type_id' => $type->id, 'starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addMinutes(30), 'status' => Appointment::CONFIRMED, 'token' => str_repeat('a', 40), 'slot_key' => 'x']);

        $this->actingAs($this->owner())->get('/admin')->assertOk()
            ->assertSee('Bekijk website')
            ->assertSee('De website is online.')
            ->assertSee('Wat aandacht nodig heeft')
            ->assertSee('Ongelezen berichten')
            ->assertSee('Nieuwe leads')
            ->assertSee('Voorstellen bekeken, nog geen besluit')
            ->assertSee('Komende afspraken')
            ->assertSee('Bezoek, 7 dagen')
            ->assertSee('/nl');
    }

    public function test_it_says_when_everything_is_done_and_when_the_site_is_offline(): void
    {
        $this->publishSite();
        $this->actingAs($this->owner())->get('/admin')->assertSee('Alles is bij.');

        Settings::save('offline', ['enabled' => true]);
        $this->get('/admin')->assertSee('De website is offline')->assertSee('Zet weer online');
    }

    public function test_an_editor_sees_no_inbox_or_proposals(): void
    {
        $this->publishSite();
        Inquiry::create(['name' => 'A', 'email' => 'a@example.test', 'message' => 'Hallo', 'locale' => 'nl']);

        $this->actingAs($this->editor())->get('/admin')->assertOk()
            ->assertDontSee('Nieuw voorstel')
            ->assertDontSee('Open voorstellen');
    }
}
