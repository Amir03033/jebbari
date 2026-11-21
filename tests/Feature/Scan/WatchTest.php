<?php

namespace Tests\Feature\Scan;

use App\Mail\WatchConfirm;
use App\Mail\WatchDown;
use App\Mail\WatchReport;
use App\Models\Scan;
use App\Models\ScanWatch;
use App\Scan\FetchFailed;
use App\Scan\Scanner;
use App\Scan\Watch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Mockery;
use Tests\Support\PublishesSite;
use Tests\TestCase;

class WatchTest extends TestCase
{
    use PublishesSite, RefreshDatabase;

    private const SCORES = ['security' => 90, 'seo' => 80, 'speed' => 70, 'accessibility' => 100];

    protected function setUp(): void
    {
        parent::setUp();
        $this->publishSite();
        Mail::fake();
    }

    private function doneScan(array $o = []): Scan
    {
        return Scan::create($o + [
            'url' => 'https://bakkerij.example', 'domain' => 'bakkerij.example', 'email' => 'klant@example.com',
            'locale' => 'nl', 'ip_hash' => 'x', 'status' => Scan::DONE, 'scores' => self::SCORES,
            'results' => ['security' => [], 'seo' => [], 'speed' => [], 'accessibility' => []], 'finished_at' => now(),
        ]);
    }

    private function scannerReturns(array $scores): void
    {
        $scanner = Mockery::mock(Scanner::class);
        $scanner->shouldReceive('scan')->andReturn(['url' => 'https://bakkerij.example', 'scores' => $scores, 'results' => ['security' => [], 'seo' => [], 'speed' => [], 'accessibility' => []]]);
        $this->app->instance(Scanner::class, $scanner);
    }

    private function confirmedWatch(array $o = []): ScanWatch
    {
        return ScanWatch::create($o + [
            'email' => 'klant@example.com', 'url' => 'https://bakkerij.example', 'domain' => 'bakkerij.example', 'locale' => 'nl',
            'last_scores' => self::SCORES, 'confirmed_at' => now()->subMonth(), 'next_run_at' => now()->subMinute(),
        ]);
    }

    public function test_the_report_offers_the_watch_and_a_pdf_button(): void
    {
        $scan = $this->doneScan();

        $this->get('/nl/scan/'.$scan->token)->assertOk()
            ->assertSee('Houd mijn website in de gaten')
            ->assertSee('klant@example.com')
            ->assertSee('data-print', false)
            ->assertSee('Opslaan als pdf');
    }

    public function test_signing_up_needs_consent_and_sends_a_confirmation(): void
    {
        $scan = $this->doneScan();

        $this->post('/nl/scan/'.$scan->token.'/watch', [])->assertSessionHasErrors('consent');
        $this->assertSame(0, ScanWatch::count());

        $this->post('/nl/scan/'.$scan->token.'/watch', ['consent' => '1'])->assertRedirect();
        $this->followRedirects($this->post('/nl/scan/'.$scan->token.'/watch', ['consent' => '1']))->assertSee('Bijna klaar');

        $watch = ScanWatch::sole();
        $this->assertNull($watch->confirmed_at);
        $this->assertSame(self::SCORES, $watch->last_scores);
        Mail::assertSent(WatchConfirm::class, fn ($m) => $m->hasTo('klant@example.com'));
    }

    public function test_the_signed_link_confirms_and_schedules_the_first_run(): void
    {
        $watch = ScanWatch::create(['email' => 'k@example.com', 'url' => 'https://bakkerij.example', 'domain' => 'bakkerij.example', 'locale' => 'nl']);

        $this->get('/scan-watch/'.$watch->id.'/confirm')->assertForbidden();
        $this->get(URL::temporarySignedRoute('scan.watch.confirm', now()->addDay(), ['watch' => $watch->id]))
            ->assertOk()->assertSee('De watch staat aan');

        $this->assertNotNull($watch->fresh()->confirmed_at);
        $this->assertTrue($watch->fresh()->next_run_at->between(now()->addMonth()->subMinute(), now()->addMonth()->addMinute()));
    }

    public function test_at_most_three_watched_sites_per_address(): void
    {
        foreach (['a', 'b', 'c'] as $d) {
            $this->confirmedWatch(['domain' => "$d.example", 'url' => "https://$d.example"]);
        }

        $this->assertSame('limit', Watch::request($this->doneScan(), 'x'));
        $this->assertSame('already', Watch::request($this->doneScan(['domain' => 'a.example']), 'x'));
    }

    public function test_a_due_watch_runs_a_new_check_and_mails_the_comparison(): void
    {
        $watch = $this->confirmedWatch();
        $this->scannerReturns(['security' => 95, 'seo' => 80, 'speed' => 72, 'accessibility' => 100]);

        $this->artisan('scan:watch')->assertSuccessful();

        $scan = Scan::where('scan_watch_id', $watch->id)->sole();
        $this->assertSame(Scan::DONE, $scan->status);
        $watch->refresh();
        $this->assertSame(95, $watch->last_scores['security']);
        $this->assertSame(1, $watch->runs);
        $this->assertTrue($watch->next_run_at->isFuture());

        Mail::assertSent(WatchReport::class, function (WatchReport $m) {
            $html = $m->render();

            return $m->hasTo('klant@example.com')
                && str_contains($html, '95/100 (+5)')
                && str_contains($html, 'Afmelden')
                && ! str_contains($m->envelope()->subject, 'Let op');
        });

        // Not due again until next month.
        $this->artisan('scan:watch');
        $this->assertSame(1, Scan::where('scan_watch_id', $watch->id)->count());
    }

    public function test_a_clear_drop_is_flagged(): void
    {
        $this->confirmedWatch();
        $this->scannerReturns(['security' => 70, 'seo' => 80, 'speed' => 70, 'accessibility' => 100]);

        $this->artisan('scan:watch');

        Mail::assertSent(WatchReport::class, fn (WatchReport $m) => str_contains($m->envelope()->subject, 'scoort lager')
            && str_contains($m->render(), 'Laat jebbari dit oplossen'));
    }

    public function test_an_unreachable_site_gets_a_plain_warning(): void
    {
        $this->confirmedWatch();
        $scanner = Mockery::mock(Scanner::class);
        $scanner->shouldReceive('scan')->andThrow(new FetchFailed('timeout'));
        $this->app->instance(Scanner::class, $scanner);

        $this->artisan('scan:watch');

        Mail::assertSent(WatchDown::class, fn (WatchDown $m) => str_contains($m->render(), 'niet binnen 10 seconden'));
        Mail::assertNotSent(WatchReport::class);
    }

    public function test_unsubscribing_deletes_the_watch(): void
    {
        $watch = $this->confirmedWatch();

        $this->get(URL::signedRoute('scan.watch.stop', ['watch' => $watch->id]))->assertOk()->assertSee('Afgemeld');

        $this->assertSame(0, ScanWatch::count());
    }

    public function test_a_monthly_report_page_does_not_offer_another_watch(): void
    {
        $watch = $this->confirmedWatch();
        $scan = $this->doneScan(['scan_watch_id' => $watch->id]);

        $this->get('/nl/scan/'.$scan->token)->assertOk()->assertDontSee('Houd mijn website in de gaten');
    }
}
