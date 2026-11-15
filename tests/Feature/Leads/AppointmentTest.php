<?php

namespace Tests\Feature\Leads;

use App\Jobs\SendAppointmentReminder;
use App\Leads\Booking;
use App\Leads\SlotFinder;
use App\Leads\SlotTaken;
use App\Mail\AppointmentBooked;
use App\Mail\AppointmentCancelled;
use App\Mail\AppointmentReminder;
use App\Mail\NewLead;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\AvailabilityRule;
use App\Models\BlockedDate;
use App\Models\Lead;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\Support\PublishesSite;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use LeadTestHelpers, PublishesSite, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publishSite();
        Settings::save('contact', ['email' => 'hallo@jebbari.test']);
        // Tuesday 20 October 2026, 08:00 in Amsterdam (summer time, UTC+2).
        $this->travelTo(CarbonImmutable::parse('2026-10-20 08:00', 'Europe/Amsterdam'));
    }

    private function slot(string $local): CarbonImmutable
    {
        return CarbonImmutable::parse($local, 'Europe/Amsterdam')->utc();
    }

    private function lead(): Lead
    {
        return Lead::create(['source' => 'appointment', 'name' => 'Sara', 'email' => 'sara@example.com', 'locale' => 'nl']);
    }

    private function book(string $local, array $extra = []): TestResponse
    {
        $type = AppointmentType::first();

        return $this->post('/nl/afspraak/'.$type->id.'/'.$this->slot($local)->format('Y-m-d\TH:i'), [
            'name' => 'Sara', 'email' => 'sara@example.com', 'privacy' => '1', '_t' => $this->stamp(), ...$extra,
        ]);
    }

    public function test_slots_follow_opening_hours_notice_blocked_days_and_type_rules(): void
    {
        $type = $this->onlineType(30);
        $this->weekdayMornings();
        BlockedDate::create(['date' => '2026-10-22']);
        // Saturday only for another type: not offered here.
        $other = $this->onlineType(60);
        AvailabilityRule::create(['weekday' => 6, 'starts_at' => '10:00', 'ends_at' => '12:00', 'appointment_type_id' => $other->id]);

        $days = app(SlotFinder::class)->days($type);

        $this->assertArrayNotHasKey('2026-10-20', $days, 'today is within the 24 hour notice');
        $this->assertArrayHasKey('2026-10-21', $days);
        $this->assertArrayNotHasKey('2026-10-22', $days, 'blocked day');
        $this->assertArrayNotHasKey('2026-10-24', $days, 'Saturday rule belongs to another type');
        $this->assertCount(6, $days['2026-10-21'], '09:00 to 12:00 in 30 minute steps');
        $this->assertSame('09:00', $days['2026-10-21'][0]->timezone('Europe/Amsterdam')->format('H:i'));
        $this->assertSame('11:30', end($days['2026-10-21'])->timezone('Europe/Amsterdam')->format('H:i'), 'the last slot still ends by 12:00');
        $this->assertArrayHasKey('2026-10-31', app(SlotFinder::class)->days($other));
        $this->assertLessThanOrEqual(config('leads.horizon_days') + 1, count(array_keys($days)));
    }

    public function test_wall_clock_times_hold_across_the_switch_to_winter_time(): void
    {
        $type = $this->onlineType();
        $this->weekdayMornings();
        $days = app(SlotFinder::class)->days($type);

        // Friday 23 October is summer time (UTC+2), Monday 26 October winter time (UTC+1).
        $this->assertSame('07:00', $days['2026-10-23'][0]->format('H:i'), '09:00 Amsterdam = 07:00 UTC in summer');
        $this->assertSame('08:00', $days['2026-10-26'][0]->format('H:i'), '09:00 Amsterdam = 08:00 UTC in winter');
        $this->assertSame('09:00', $days['2026-10-26'][0]->timezone('Europe/Amsterdam')->format('H:i'));
    }

    public function test_booked_slots_and_their_buffer_are_no_longer_offered(): void
    {
        $type = $this->onlineType(30);
        $this->weekdayMornings();
        app(Booking::class)->book($this->lead(), $type, $this->slot('2026-10-21 10:00'));

        $times = array_map(fn ($s) => $s->timezone('Europe/Amsterdam')->format('H:i'), app(SlotFinder::class)->days($type)['2026-10-21']);

        // 10:00-10:30 booked, with 15 minutes either side: 09:30 and 10:30 overlap the buffer.
        $this->assertSame(['09:00', '11:00', '11:30'], $times);
    }

    public function test_a_slot_cannot_be_booked_twice(): void
    {
        Mail::fake();
        $type = $this->onlineType();
        $this->weekdayMornings();
        $booking = app(Booking::class);
        $start = $this->slot('2026-10-21 09:00');

        $booking->book($this->lead(), $type, $start);

        $this->expectException(SlotTaken::class);
        $booking->book($this->lead(), $type, $start);
    }

    public function test_the_unique_index_stops_a_double_booking_that_slips_past_the_check(): void
    {
        $type = $this->onlineType();
        $lead = $this->lead();
        $start = $this->slot('2026-10-21 09:00');
        $row = ['lead_id' => $lead->id, 'appointment_type_id' => $type->id, 'starts_at' => $start, 'ends_at' => $start->addMinutes(30), 'slot_key' => Appointment::slotKey($start)];
        Appointment::create($row);

        $this->expectException(QueryException::class);
        Appointment::create($row);
    }

    public function test_booking_through_the_site_confirms_mails_and_queues_the_reminder(): void
    {
        Mail::fake();
        Queue::fake();
        $type = $this->onlineType();
        $this->weekdayMornings();

        $html = $this->get('/nl/afspraak')->assertOk()->assertSee('Kies een dag')->assertSee('Alle tijden in Nederlandse tijd', false)->getContent();
        $this->assertSame(0, preg_match('/<[a-z][^>]*\sstyle="/i', $html));
        $details = '/nl/afspraak/'.$type->id.'/'.$this->slot('2026-10-21 09:30')->format('Y-m-d\TH:i');
        $this->get($details)->assertOk()->assertSee('09:30')->assertSee('name="privacy"', false);

        $this->book('2026-10-21 09:30', ['privacy' => null])->assertSessionHasErrors('privacy');
        $response = $this->book('2026-10-21 09:30', ['phone' => '06 1234 5678', 'message' => 'Een nieuwe website']);

        $appointment = Appointment::sole();
        $response->assertRedirect('/nl/afspraak/beheer/'.$appointment->token);
        $this->assertSame('2026-10-21 07:30', $appointment->starts_at->format('Y-m-d H:i'), 'stored in UTC');
        $this->assertSame(['Sara', 'sara@example.com', '06 1234 5678', 'appointment'], [$appointment->lead->name, $appointment->lead->email, $appointment->lead->phone, $appointment->lead->source]);

        Mail::assertSent(AppointmentBooked::class, fn ($m) => $m->hasTo('sara@example.com'));
        Mail::assertSent(NewLead::class, fn ($m) => $m->hasTo('hallo@jebbari.test') && $m->event === 'appointment');
        Queue::assertPushed(SendAppointmentReminder::class, fn ($job) => $job->delay->equalTo($appointment->starts_at->copy()->subHours(24)));

        $mail = new AppointmentBooked($appointment->load('lead', 'type'));
        $this->assertStringContainsString('woensdag 21 oktober', $mail->render());
        $ics = $mail->attachments()[0];
        $this->assertSame('afspraak-jebbari.ics', $ics->as);

        $this->get('/nl/afspraak/beheer/'.$appointment->token)->assertOk()->assertSee(__('leads.appointment.manage_title'))->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        // The same moment is gone for the next visitor.
        $this->flushSession();
        $this->get($details)->assertRedirect()->assertSessionHasErrors('slot');
        $this->book('2026-10-21 09:30')->assertSessionHasErrors('slot');
        $this->assertSame(1, Appointment::count());
    }

    public function test_the_ics_file_is_valid_and_in_utc(): void
    {
        $type = $this->onlineType();
        $this->weekdayMornings();
        $appointment = app(Booking::class)->book($this->lead(), $type, $this->slot('2026-10-26 09:00'));

        $ics = $this->get('/nl/afspraak/beheer/'.$appointment->token.'/agenda.ics')->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')->getContent();

        $this->assertStringStartsWith("BEGIN:VCALENDAR\r\n", $ics);
        $this->assertStringContainsString("DTSTART:20261026T080000Z\r\n", $ics, '09:00 winter time');
        $this->assertStringContainsString("DTEND:20261026T083000Z\r\n", $ics);
        $this->assertStringContainsString('UID:'.$appointment->token.'@', $ics);
        foreach (explode("\r\n", $ics) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line));
        }
    }

    public function test_rescheduling_frees_the_old_slot_and_keeps_the_lead(): void
    {
        Mail::fake();
        Queue::fake();
        $type = $this->onlineType();
        $this->weekdayMornings();
        $old = app(Booking::class)->book($this->lead(), $type, $this->slot('2026-10-21 09:00'));

        $this->get('/nl/afspraak/'.$type->id.'?verzet='.$old->token)->assertOk()->assertSee('Je verzet je afspraak');
        $this->post('/nl/afspraak/'.$type->id.'/'.$this->slot('2026-10-23 11:00')->format('Y-m-d\TH:i').'?verzet='.$old->token, ['verzet' => $old->token])->assertRedirect();

        $old->refresh();
        $new = Appointment::query()->where('status', 'confirmed')->sole();
        $this->assertSame([Appointment::CANCELLED, null], [$old->status, $old->slot_key]);
        $this->assertSame($old->lead_id, $new->lead_id);
        $this->assertSame('2026-10-23 09:00', $new->starts_at->format('Y-m-d H:i'));
        Mail::assertSent(AppointmentBooked::class, fn ($m) => $m->rescheduled);
        Mail::assertSent(NewLead::class, fn ($m) => $m->event === 'appointment_moved');

        // The old moment can be booked again.
        $this->assertTrue(app(SlotFinder::class)->isFree($type, $this->slot('2026-10-21 09:00')));
    }

    public function test_cancelling_via_the_link_mails_both_sides(): void
    {
        Mail::fake();
        $type = $this->onlineType();
        $this->weekdayMornings();
        $appointment = app(Booking::class)->book($this->lead(), $type, $this->slot('2026-10-21 09:00'));

        $this->post('/nl/afspraak/beheer/'.$appointment->token.'/annuleren')->assertRedirect('/nl/afspraak/beheer/'.$appointment->token);

        $this->assertSame(Appointment::CANCELLED, $appointment->fresh()->status);
        Mail::assertSent(AppointmentCancelled::class, fn ($m) => ! $m->toOwner && $m->hasTo('sara@example.com'));
        Mail::assertSent(AppointmentCancelled::class, fn ($m) => $m->toOwner && $m->hasTo('hallo@jebbari.test'));
        $this->get('/nl/afspraak/beheer/'.$appointment->token)->assertSee(__('leads.appointment.cancelled_title'))->assertDontSee(__('leads.appointment.cancel_button'));
        $this->get('/nl/afspraak/beheer/'.str_repeat('z', 40))->assertNotFound();
    }

    public function test_the_reminder_is_sent_once_and_never_for_a_cancelled_appointment(): void
    {
        Mail::fake();
        $type = $this->onlineType();
        $this->weekdayMornings();
        $appointment = app(Booking::class)->book($this->lead(), $type, $this->slot('2026-10-21 09:00'));

        $this->travelTo($appointment->starts_at->copy()->subHours(24));
        (new SendAppointmentReminder($appointment))->handle();
        (new SendAppointmentReminder($appointment))->handle();
        Mail::assertSent(AppointmentReminder::class, 1);
        $this->assertNotNull($appointment->fresh()->reminder_sent_at);

        $other = app(Booking::class)->book($this->lead(), $type, $this->slot('2026-10-22 11:00'));
        app(Booking::class)->cancel($other->load('lead', 'type'));
        (new SendAppointmentReminder($other))->handle();
        Mail::assertSent(AppointmentReminder::class, 1);
    }

    public function test_empty_states_without_types_or_free_times(): void
    {
        $this->get('/nl/afspraak')->assertOk()->assertSee(__('leads.appointment.no_types_title'));

        $this->onlineType();
        $this->get('/nl/afspraak')->assertOk()->assertSee(__('leads.appointment.no_slots_title'));
        $this->get('/en/appointment')->assertOk()->assertSee('No times are free right now');
    }

    public function test_bad_slot_urls_are_refused(): void
    {
        $type = $this->onlineType();
        $this->weekdayMornings();

        // Outside the hours, in the past, and not on the 30 minute grid.
        foreach (['2026-10-21T15:00', '2026-10-19T07:00', '2026-10-21T07:10'] as $slot) {
            $this->get('/nl/afspraak/'.$type->id.'/'.$slot)->assertRedirect()->assertSessionHasErrors('slot');
        }
        $this->get('/nl/afspraak/999')->assertNotFound();
    }
}
