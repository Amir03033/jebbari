<?php

namespace Tests\Feature\Admin;

use App\Leads\Booking;
use App\Models\AppointmentType;
use App\Models\Lead;
use App\Models\PriceItem;
use Carbon\CarbonImmutable;
use Database\Seeders\LeadFunnelSeeder;
use Tests\Feature\Leads\LeadTestHelpers;
use Tests\Support\AdminTestCase;

class LeadAdminTest extends AdminTestCase
{
    use LeadTestHelpers;

    public function test_every_funnel_page_renders_for_the_owner(): void
    {
        $this->prices();
        $type = $this->onlineType();
        $this->weekdayMornings();
        $lead = Lead::create(['source' => 'wizard', 'name' => 'Sara', 'email' => 'sara@example.com', 'estimate_min' => 2000, 'estimate_max' => 4000,
            'choices' => ['calculator' => ['project' => 'website', 'extras' => ['cms']], 'wizard' => ['summary' => 'Een site', 'examples' => ['https://example.com']], 'scan' => ['url' => 'https://example.com', 'report' => 'https://jebbari.nl/nl/scan/x']]]);
        $this->travelTo(CarbonImmutable::parse('2026-10-20 08:00', 'Europe/Amsterdam'));
        app(Booking::class)->book($lead, $type, CarbonImmutable::parse('2026-10-21 09:00', 'Europe/Amsterdam'));
        $owner = $this->owner();

        foreach ([
            '/admin/leads', '/admin/leads/'.$lead->id, '/admin/appointments',
            '/admin/price-items', '/admin/price-items/create', '/admin/price-items/'.PriceItem::first()->id.'/edit',
            '/admin/appointment-types', '/admin/appointment-types/create', '/admin/appointment-types/'.$type->id.'/edit',
            '/admin/availability-rules', '/admin/availability-rules/create',
            '/admin/blocked-dates', '/admin/blocked-dates/create',
        ] as $url) {
            $this->actingAs($owner)->get($url)->assertOk();
        }

        $this->actingAs($owner)->get('/admin/leads/'.$lead->id)->assertSee('Sara')->assertSee('€ 2.000 – € 4.000')->assertSee('Een site');
    }

    public function test_inbox_roles_see_leads_but_only_the_owner_sets_prices_and_hours(): void
    {
        $this->actingAs($this->editor())->get('/admin/leads')->assertOk();
        $this->actingAs($this->support())->get('/admin/appointments')->assertOk();
        foreach (['/admin/price-items', '/admin/appointment-types', '/admin/availability-rules', '/admin/blocked-dates'] as $url) {
            $this->actingAs($this->editor())->get($url)->assertForbidden();
        }
    }

    public function test_the_seeder_adds_structure_without_prices_and_never_overwrites(): void
    {
        $this->seed(LeadFunnelSeeder::class);

        $this->assertSame(13, PriceItem::count());
        $this->assertSame(0, PriceItem::query()->where('is_active', true)->count(), 'nothing shows until the owner fills in prices');
        $this->assertNull(PriceItem::first()->price_min);
        $this->assertSame(1, AppointmentType::query()->where('is_active', true)->count(), 'only the online introduction is on');

        PriceItem::query()->where('key', 'website')->update(['price_min' => 1500, 'price_max' => 3000, 'is_active' => true]);
        $this->seed(LeadFunnelSeeder::class);
        $this->assertSame(1500, PriceItem::query()->where('key', 'website')->value('price_min'));
        $this->assertSame(13, PriceItem::count());
        $this->assertSame(3, AppointmentType::count());
    }
}
