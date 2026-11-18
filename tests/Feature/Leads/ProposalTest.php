<?php

namespace Tests\Feature\Leads;

use App\Filament\Resources\Proposals\Pages\CreateProposal;
use App\Filament\Resources\Proposals\Pages\EditProposal;
use App\Mail\ProposalAccepted;
use App\Mail\ProposalDecision;
use App\Mail\ProposalSent;
use App\Models\Lead;
use App\Models\Proposal;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\Support\CreatesUsers;
use Tests\Support\PublishesSite;
use Tests\TestCase;

class ProposalTest extends TestCase
{
    use CreatesUsers, PublishesSite, RefreshDatabase;

    private function proposal(array $o = []): Proposal
    {
        return Proposal::create($o + [
            'locale' => 'nl',
            'client_name' => 'Sanne de Vries',
            'client_email' => 'sanne@example.test',
            'client_company' => 'Bakkerij De Vries',
            'title' => 'Nieuwe website voor Bakkerij De Vries',
            'intro' => "Eerste alinea.\n\nTweede alinea.",
            'scope' => [['title' => 'Ontwerp', 'description' => 'Een klikbaar ontwerp.'], ['title' => 'Bouw', 'description' => null]],
            'timeline' => [['phase' => 'Ontwerp', 'duration' => 'week 1 tot 2']],
            'prices' => [
                ['description' => 'Website', 'amount' => 4000, 'period' => 'once'],
                ['description' => 'Bestelmodule', 'amount' => 1500, 'period' => 'once'],
                ['description' => 'Onderhoud en hosting', 'amount' => 40, 'period' => 'month'],
            ],
            'valid_until' => now()->addDays(30)->toDateString(),
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->publishSite();
        Settings::save('contact', ['email' => 'hallo@example.test']);
        Mail::fake();
    }

    public function test_the_client_sees_the_proposal_with_totals_and_it_is_private(): void
    {
        $p = $this->proposal();

        $this->get('/nl/voorstel/'.$p->token)->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Nieuwe website voor Bakkerij De Vries')
            ->assertSee('Voor Sanne de Vries, Bakkerij De Vries')
            ->assertSee('Tweede alinea.')
            ->assertSee('€ 5.500')
            ->assertSee('€ 40')
            ->assertSee('per maand')
            ->assertSee('name="robots" content="noindex, nofollow"', false);

        $this->assertSame(1, $p->fresh()->view_count);
        $this->assertSame('viewed', $p->fresh()->state());

        $this->get('/nl/voorstel/'.str_repeat('a', 40))->assertNotFound();
    }

    public function test_views_by_team_members_are_not_counted(): void
    {
        $p = $this->proposal();

        $this->actingAs($this->owner())->get('/nl/voorstel/'.$p->token)->assertOk();

        $this->assertSame(0, $p->fresh()->view_count);
        $this->assertNull($p->fresh()->first_viewed_at);
    }

    public function test_accepting_needs_a_name_and_the_checkbox(): void
    {
        $p = $this->proposal();

        $this->from('/nl/voorstel/'.$p->token)->post('/nl/voorstel/'.$p->token.'/akkoord', ['name' => ''])
            ->assertSessionHasErrors(['name', 'agree']);

        $this->assertNull($p->fresh()->accepted_at);
    }

    public function test_the_client_accepts_and_both_sides_get_an_email(): void
    {
        $lead = Lead::create(['source' => 'wizard', 'name' => 'Sanne', 'email' => 'sanne@example.test']);
        $p = $this->proposal(['lead_id' => $lead->id]);

        $this->post('/nl/voorstel/'.$p->token.'/akkoord', ['name' => 'Sanne de Vries', 'agree' => '1'])
            ->assertRedirect($p->url());

        $p->refresh();
        $this->assertSame('accepted', $p->state());
        $this->assertSame('Sanne de Vries', $p->accepted_name);
        $this->assertNotNull($p->accepted_ip_hash);
        $this->assertSame('won', $lead->fresh()->status);

        Mail::assertSent(ProposalAccepted::class, fn ($m) => $m->hasTo('sanne@example.test'));
        Mail::assertSent(ProposalDecision::class, fn ($m) => $m->hasTo('hallo@example.test'));

        $this->get($p->url())->assertSee('Akkoord gegeven door Sanne de Vries')->assertDontSee('Akkoord geven</');

        // A second decision changes nothing.
        $this->post('/nl/voorstel/'.$p->token.'/afwijzen', ['reason' => 'Toch niet']);
        $this->assertNull($p->fresh()->declined_at);
    }

    public function test_the_client_declines_with_a_reason(): void
    {
        $p = $this->proposal();

        $this->post('/nl/voorstel/'.$p->token.'/afwijzen', ['reason' => 'Te duur voor nu.'])->assertRedirect($p->url());

        $this->assertSame('declined', $p->fresh()->state());
        $this->assertSame('Te duur voor nu.', $p->fresh()->decline_reason);
        Mail::assertSent(ProposalDecision::class);
        Mail::assertNotSent(ProposalAccepted::class);
    }

    public function test_an_expired_proposal_cannot_be_accepted(): void
    {
        $p = $this->proposal(['valid_until' => now()->subDay()->toDateString()]);

        $this->get($p->url())->assertSee('Dit voorstel is verlopen')->assertDontSee('name="agree"', false);
        $this->post('/nl/voorstel/'.$p->token.'/akkoord', ['name' => 'Sanne', 'agree' => '1']);

        $this->assertNull($p->fresh()->accepted_at);
    }

    public function test_an_english_proposal_reads_in_english(): void
    {
        $p = $this->proposal(['locale' => 'en', 'title' => 'New website']);

        $this->assertStringContainsString('/en/proposal/', $p->url());
        $this->get($p->url())->assertOk()->assertSee('Investment')->assertSee('Accept proposal');
    }

    public function test_the_owner_creates_a_proposal_from_a_lead_and_sends_it(): void
    {
        $lead = Lead::create(['source' => 'wizard', 'name' => 'Sanne', 'email' => 'sanne@example.test', 'company' => 'De Vries', 'locale' => 'nl']);
        $this->actingAs($this->owner());

        Livewire::withQueryParams(['lead' => $lead->id])->test(CreateProposal::class)
            ->assertSet('data.client_email', 'sanne@example.test')
            ->set('data.title', 'Nieuwe website')
            ->call('create')
            ->assertHasNoErrors();

        $p = Proposal::firstOrFail();
        $this->assertSame($lead->id, $p->lead_id);

        Livewire::test(EditProposal::class, ['record' => $p->getRouteKey()])->callAction('send');

        Mail::assertSent(ProposalSent::class, fn ($m) => $m->hasTo('sanne@example.test'));
        $this->assertNotNull($p->fresh()->sent_at);
        $this->assertSame('quote', $lead->fresh()->status);
    }

    public function test_a_decided_proposal_is_locked_in_the_admin(): void
    {
        $p = $this->proposal(['accepted_at' => now(), 'accepted_name' => 'Sanne']);
        $this->actingAs($this->owner());

        Livewire::test(EditProposal::class, ['record' => $p->getRouteKey()])
            ->assertActionHidden('send')
            ->set('data.title', 'Iets anders')
            ->call('save')
            ->assertForbidden();

        $this->assertSame('Nieuwe website voor Bakkerij De Vries', $p->fresh()->title);
    }

    public function test_only_the_owner_manages_proposals(): void
    {
        $this->actingAs($this->editor())->get('/admin/proposals')->assertForbidden();
        $this->actingAs($this->owner())->get('/admin/proposals')->assertOk();
    }
}
