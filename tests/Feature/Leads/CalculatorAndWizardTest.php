<?php

namespace Tests\Feature\Leads;

use App\Leads\PriceCalculator;
use App\Mail\EstimateMail;
use App\Mail\NewLead;
use App\Mail\RequestReceived;
use App\Models\Lead;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Tests\Support\PublishesSite;
use Tests\TestCase;

class CalculatorAndWizardTest extends TestCase
{
    use LeadTestHelpers, PublishesSite, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publishSite();
        Settings::save('contact', ['email' => 'hallo@jebbari.test']);
    }

    private function assertCspClean(string $html): void
    {
        $this->assertSame(0, preg_match('/<[a-z][^>]*\sstyle="/i', $html), 'no inline style');
        $this->assertSame(0, preg_match('/<[a-z][^>]*\son[a-z]+="/i', $html), 'no inline handlers');
        $this->assertSame(0, preg_match('#<script(?![^>]*\bsrc=)(?![^>]*application/ld\+json)#i', $html), 'no inline script');
    }

    public function test_without_prices_the_calculator_says_so_and_points_onward(): void
    {
        $this->get('/nl/prijs')->assertOk()->assertSee(__('leads.price.unavailable_title'))->assertSee('/nl/aanvraag', false)->assertDontSee('x-data="priceCalculator"', false);
    }

    public function test_the_range_is_added_up_on_the_server_from_live_items_only(): void
    {
        $this->prices();
        $calc = app(PriceCalculator::class);

        $result = $calc->calculate(['project' => 'website', 'size' => 'small', 'extras' => ['cms', 'multilingual', 'hidden', 'webapp'], 'deadline' => 'rush']);
        $this->assertSame([4300, 9000], [$result['min'], $result['max']]);
        $this->assertSame(['cms', 'multilingual'], $result['choices']['extras'], 'hidden items and items from another group are ignored');

        $this->assertNull($calc->calculate(['extras' => ['cms']]), 'a project is required');
        $this->assertNull($calc->calculate(['project' => 'cms']), 'an extra is not a project');
        $this->assertSame('€ 2.000 – € 4.000', PriceCalculator::range(2000, 4000));
    }

    public function test_the_page_shows_the_result_without_javascript_and_stays_csp_clean(): void
    {
        $this->prices();

        $html = $this->get('/nl/prijs?project=website&extras[]=cms')->assertOk()
            ->assertSee('€ 2.800 – € 5.500')->assertSee(__('leads.price.disclaimer'))->assertSee('x-data="priceCalculator"', false)->getContent();
        $this->assertCspClean($html);
        $this->assertStringNotContainsString('99999', $html, 'hidden prices never reach the page');
        $this->get('/en/pricing')->assertOk()->assertSee('Roughly what will it cost?');
    }

    public function test_request_a_quote_saves_the_recomputed_estimate_and_prefills_the_wizard(): void
    {
        $this->prices();

        $this->post('/nl/prijs', ['project' => 'website', 'extras' => ['cms'], 'next' => 'request'])->assertRedirect('/nl/aanvraag/stap/1');

        $lead = Lead::sole();
        $this->assertSame(['calculator', 2800, 5500], [$lead->source, $lead->estimate_min, $lead->estimate_max]);
        $this->assertSame('website', $lead->choice('wizard.project'));
        $this->get('/nl/aanvraag/stap/1')->assertOk()->assertSee('Uit de calculator')->assertSee('€ 2.800 – € 5.500');

        $this->post('/nl/prijs', ['project' => 'nope', 'next' => 'request'])->assertSessionHasErrors('project');
    }

    public function test_the_result_can_be_emailed(): void
    {
        Mail::fake();
        $this->prices();

        $this->post('/nl/prijs', ['project' => 'website', 'next' => 'email', 'email' => 'klant@example.com', '_t' => $this->stamp()])->assertSessionHasErrors('privacy', null, 'estimate');
        $this->post('/nl/prijs', ['project' => 'website', 'next' => 'email', 'email' => 'Klant@Example.com', 'privacy' => '1', '_t' => $this->stamp()])->assertSessionHas('estimate_sent');

        $this->assertSame('klant@example.com', Lead::sole()->email);
        Mail::assertSent(EstimateMail::class, fn ($m) => $m->hasTo('klant@example.com'));
        Mail::assertSent(NewLead::class, fn ($m) => $m->hasTo('hallo@jebbari.test') && $m->event === 'estimate');
        $this->assertStringContainsString('/nl/aanvraag/verder/'.Lead::sole()->token, (new EstimateMail(Lead::sole(), []))->render());
    }

    public function test_the_wizard_walks_five_saved_steps_and_cannot_be_skipped(): void
    {
        Mail::fake();

        $this->get('/nl/aanvraag')->assertRedirect('/nl/aanvraag/stap/1');
        $this->get('/nl/aanvraag/stap/3')->assertRedirect('/nl/aanvraag/stap/1');
        $this->assertCspClean($this->get('/nl/aanvraag/stap/1')->assertOk()->assertSee('Stap 1 van 5')->getContent());

        $this->post('/nl/aanvraag/stap/1', ['summary' => 'Kort'])->assertSessionHasErrors('summary');
        $this->post('/nl/aanvraag/stap/1', ['summary' => 'Een boekingssysteem voor mijn kapsalon.'])->assertRedirect('/nl/aanvraag/stap/2');
        $this->post('/nl/aanvraag/stap/2', ['goal' => 'Minder bellen', 'audience' => 'Klanten in Zwolle'])->assertRedirect('/nl/aanvraag/stap/3');
        $this->post('/nl/aanvraag/stap/3', ['budget' => 'bogus'])->assertSessionHasErrors('budget');
        $this->post('/nl/aanvraag/stap/3', ['budget' => '2.5-7.5k', 'deadline' => '1-3m'])->assertRedirect('/nl/aanvraag/stap/4');
        $this->post('/nl/aanvraag/stap/4', ['examples' => ['javascript:alert(1)']])->assertSessionHasErrors('examples.0');
        $this->post('/nl/aanvraag/stap/4', ['features' => 'Online betalen', 'examples' => ['https://barbercuts.nl', '', '']])->assertRedirect('/nl/aanvraag/stap/5');

        $lead = Lead::sole();
        $this->assertNull($lead->email);
        $this->assertSame(5, $lead->wizard_step);
        $this->assertSame(['https://barbercuts.nl'], $lead->choice('wizard.examples'));

        // Back to an earlier step keeps the answers.
        $this->get('/nl/aanvraag/stap/2')->assertSee('Minder bellen');

        $this->post('/nl/aanvraag/stap/5', ['name' => 'Sara', 'email' => 'sara@example.com', 'privacy' => '1', '_t' => $this->stamp()])->assertRedirect('/nl/aanvraag/verzonden');

        $lead->refresh();
        $this->assertNotNull($lead->submitted_at);
        $this->assertSame(['Sara', 'sara@example.com', true], [$lead->name, $lead->email, $lead->contact_consent]);
        Mail::assertSent(RequestReceived::class, fn ($m) => $m->hasTo('sara@example.com'));
        Mail::assertSent(NewLead::class, fn ($m) => $m->event === 'request');
        $this->assertStringContainsString('Online betalen', (new NewLead($lead, 'request'))->render());
        $this->get('/nl/aanvraag/verzonden')->assertOk()->assertSee(__('leads.request.done_title'));

        // A new visit starts a fresh request; the sent one stays as it is.
        $this->get('/nl/aanvraag')->assertRedirect('/nl/aanvraag/stap/1');
    }

    public function test_the_last_step_is_protected_against_bots(): void
    {
        Mail::fake();
        $this->post('/nl/aanvraag/stap/1', ['summary' => 'Een boekingssysteem voor mijn kapsalon.']);
        Lead::query()->update(['wizard_step' => 5]);

        $this->post('/nl/aanvraag/stap/5', ['name' => 'Bot', 'email' => 'bot@example.com', 'privacy' => '1', 'website' => 'spam', '_t' => $this->stamp()])->assertRedirect('/nl/aanvraag/verzonden');
        $this->assertNull(Lead::sole()->submitted_at);

        $this->post('/nl/aanvraag/stap/5', ['name' => 'Snel', 'email' => 'snel@example.com', 'privacy' => '1', '_t' => Crypt::encryptString((string) time())])->assertSessionHasErrors('name');
        $this->post('/nl/aanvraag/stap/5', ['name' => 'Sara', 'email' => 'sara@example.com', '_t' => $this->stamp()])->assertSessionHasErrors('privacy');
        Mail::assertNothingSent();
    }

    public function test_the_resume_link_brings_a_visitor_back_to_their_step(): void
    {
        $this->post('/nl/aanvraag/stap/1', ['summary' => 'Een boekingssysteem voor mijn kapsalon.']);
        $token = Lead::sole()->token;
        $this->flushSession();

        $this->get('/nl/aanvraag/verder/'.$token)->assertRedirect('/nl/aanvraag/stap/2');
        $this->get('/nl/aanvraag/stap/1')->assertSee('kapsalon');
        $this->get('/nl/aanvraag/verder/'.str_repeat('x', 40))->assertNotFound();
    }

    public function test_anonymous_drafts_are_pruned_but_real_leads_stay(): void
    {
        $draft = Lead::create(['source' => 'wizard']);
        $draft->forceFill(['updated_at' => now()->subDays(31)])->saveQuietly();
        $real = Lead::create(['source' => 'wizard', 'email' => 'a@example.com']);
        $real->forceFill(['updated_at' => now()->subDays(400)])->saveQuietly();

        $this->artisan('model:prune', ['--model' => [Lead::class]])->assertSuccessful();

        $this->assertModelMissing($draft);
        $this->assertModelExists($real);
    }
}
