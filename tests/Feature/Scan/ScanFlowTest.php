<?php

namespace Tests\Feature\Scan;

use App\Jobs\RunScan;
use App\Mail\ScanReady;
use App\Models\Scan;
use App\Scan\CertificateInspector;
use App\Scan\UrlGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Support\PublishesSite;
use Tests\TestCase;

class ScanFlowTest extends TestCase
{
    use PublishesSite, RefreshDatabase;

    private const PAGE = <<<'HTML'
        <!doctype html><html lang="nl"><head><meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Bakkerij Jansen · Vers brood in Zwolle sinds 1952</title></head>
        <body><h1>Vers brood</h1><img src="/a.jpg"></body></html>
        HTML;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publishSite();

        // No real DNS, certificates or network in tests.
        $this->app->instance(UrlGuard::class, new UrlGuard(fn (string $host) => $host === 'intern.example' ? ['10.0.0.8'] : ['93.184.216.34']));
        $this->app->instance(CertificateInspector::class, new class extends CertificateInspector
        {
            public function inspect(string $host, string $ip): ?array
            {
                return ['valid' => true, 'days_left' => 80];
            }
        });
    }

    private function form(array $overrides = []): array
    {
        return [
            'url' => 'bakkerij.example',
            'email' => 'Klant@Example.com',
            'privacy' => '1',
            'contact_consent' => '1',
            'website' => '',
            '_t' => Crypt::encryptString((string) (time() - 30)),
            ...$overrides,
        ];
    }

    private function assertCspClean(string $html): void
    {
        $this->assertSame(0, preg_match('/<[a-z][^>]*\sstyle="/i', $html), 'no inline style');
        $this->assertSame(0, preg_match('/<[a-z][^>]*\son[a-z]+="/i', $html), 'no inline handlers');
        $this->assertSame(0, preg_match('#<script(?![^>]*\bsrc=)(?![^>]*application/ld\+json)#i', $html), 'no inline script');
    }

    public function test_the_form_renders_in_both_languages(): void
    {
        $nl = $this->get('/nl/scan')->assertOk()->assertSee('Hoe staat je website ervoor?')->assertSee('name="privacy"', false)->getContent();
        $this->assertCspClean($nl);
        $this->assertStringContainsString('<link rel="canonical" href="'.url('/nl/scan').'">', $nl);
        $this->assertStringContainsString('hreflang="en" href="'.url('/en/scan').'"', $nl);
        $this->get('/en/scan')->assertOk()->assertSee('How is your website doing?');
        $this->assertStringNotContainsString('challenges.cloudflare.com', $nl, 'Turnstile stays off without keys');
    }

    public function test_turnstile_is_loaded_and_allowed_only_when_configured(): void
    {
        config(['scan.turnstile.site_key' => 'site', 'scan.turnstile.secret_key' => 'secret']);

        $response = $this->get('/nl/scan')->assertSee('data-sitekey="site"', false)->assertSee('https://challenges.cloudflare.com/turnstile/v0/api.js', false);
        $this->assertStringContainsString("script-src 'self' https://challenges.cloudflare.com", $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString('frame-src https://challenges.cloudflare.com', $response->headers->get('Content-Security-Policy'));
        $this->assertStringNotContainsString('cloudflare', $this->get('/nl/contact')->headers->get('Content-Security-Policy'));

        Queue::fake();
        Http::fake(['challenges.cloudflare.com/*' => Http::sequence()->push(['success' => false])->push(['success' => true])]);
        $this->post('/nl/scan', $this->form())->assertSessionHasErrors('url');
        $this->post('/nl/scan', $this->form(['cf-turnstile-response' => 'rejected']))->assertSessionHasErrors('url');
        $this->assertSame(0, Scan::count());

        $this->post('/nl/scan', $this->form(['cf-turnstile-response' => 'token']))->assertSessionHasNoErrors();
        $this->assertSame(1, Scan::count());
    }

    public function test_a_valid_request_creates_a_scan_and_queues_it(): void
    {
        Queue::fake();

        $response = $this->post('/nl/scan', $this->form());
        $scan = Scan::sole();

        $response->assertRedirect('/nl/scan/'.$scan->token);
        $this->assertSame(['https://bakkerij.example', 'bakkerij.example', 'klant@example.com', 'nl', true], [$scan->url, $scan->domain, $scan->email, $scan->locale, $scan->contact_consent]);
        $this->assertSame(40, strlen($scan->token));
        $this->assertSame(64, strlen($scan->ip_hash));
        $this->assertNotSame('127.0.0.1', $scan->ip_hash);
        Queue::assertPushed(RunScan::class);
    }

    public function test_the_form_is_validated(): void
    {
        Queue::fake();

        $this->post('/nl/scan', $this->form(['privacy' => null]))->assertSessionHasErrors('privacy');
        $this->post('/nl/scan', $this->form(['email' => 'geen-adres']))->assertSessionHasErrors('email');
        $this->post('/nl/scan', $this->form(['url' => 'http://127.0.0.1/admin']))->assertSessionHasErrors(['url' => __('scan.error.private')]);
        $this->post('/nl/scan', $this->form(['url' => 'https://intern.example/']))->assertSessionHasErrors(['url' => __('scan.error.private')]);
        $this->post('/nl/scan', $this->form(['url' => 'https://bakkerij.example:8443/']))->assertSessionHasErrors(['url' => __('scan.error.port')]);
        $this->post('/nl/scan', $this->form(['_t' => Crypt::encryptString((string) time())]))->assertSessionHasErrors('url');

        $this->assertSame(0, Scan::count());
        Queue::assertNothingPushed();
    }

    public function test_the_honeypot_stores_nothing(): void
    {
        Queue::fake();
        $this->post('/nl/scan', $this->form(['website' => 'spam']))->assertRedirect('/nl/scan');
        $this->assertSame(0, Scan::count());
    }

    public function test_three_scans_per_visitor_per_day(): void
    {
        Queue::fake();
        RateLimiter::clear('scan:'.hash_hmac('sha256', '127.0.0.1', (string) config('app.key')));

        foreach (range(1, 3) as $i) {
            $this->post('/nl/scan', $this->form(['url' => "site{$i}.example"]))->assertRedirect();
            $this->flushSession();
        }

        $this->post('/nl/scan', $this->form())->assertSessionHasErrors(['url' => __('scan.form.limit', ['count' => 3])]);
        $this->assertSame(3, Scan::count());
    }

    public function test_a_scan_runs_and_the_report_and_mail_follow(): void
    {
        Mail::fake();
        Http::fake([
            'https://bakkerij.example/robots.txt' => Http::response("User-agent: *\nDisallow:\n"),
            'https://bakkerij.example/sitemap.xml' => Http::response('<?xml version="1.0"?><urlset></urlset>'),
            'https://bakkerij.example' => Http::response(self::PAGE, 200, ['Content-Type' => 'text/html; charset=utf-8', 'X-Content-Type-Options' => 'nosniff']),
            'http://bakkerij.example/' => Http::response('', 301, ['Location' => 'https://bakkerij.example/']),
            'www.googleapis.com/*' => Http::response(['lighthouseResult' => [
                'categories' => ['performance' => ['score' => 0.83]],
                'audits' => [
                    'largest-contentful-paint' => ['numericValue' => 2100],
                    'cumulative-layout-shift' => ['numericValue' => 0.3],
                    'total-blocking-time' => ['numericValue' => 120],
                    'total-byte-weight' => ['numericValue' => 900000],
                    'network-requests' => ['details' => ['items' => array_fill(0, 12, [])]],
                    'color-contrast' => ['score' => 1],
                ],
            ]]),
        ]);

        $scan = Scan::create(['url' => 'https://bakkerij.example', 'domain' => 'bakkerij.example', 'email' => 'klant@example.com', 'locale' => 'nl', 'ip_hash' => 'x']);
        RunScan::dispatchSync($scan);
        $scan->refresh();

        $this->assertSame(Scan::DONE, $scan->status);
        $this->assertSame(83, $scan->scores['speed']);
        $this->assertNotNull($scan->scores['security']);
        $this->assertSame('good', collect($scan->results['seo'])->firstWhere('key', 'sitemap')['status']);
        $this->assertSame('good', collect($scan->results['security'])->firstWhere('key', 'http_redirect')['status']);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'pagespeedonline') && str_contains($r->url(), 'strategy=mobile') && str_contains($r->url(), 'category=accessibility'));
        Mail::assertSent(ScanReady::class, fn ($m) => $m->hasTo('klant@example.com'));

        $mail = (new ScanReady($scan))->render();
        $this->assertStringContainsString('/nl/scan/'.$scan->token, $mail);
        $this->assertStringContainsString('Laat jebbari dit oplossen', $mail);

        $response = $this->get('/nl/scan/'.$scan->token)->assertOk()
            ->assertSee('Rapport voor bakkerij.example')
            ->assertSee('Eerst aanpakken')
            ->assertSee('Cumulative Layout Shift')
            ->assertSee(__('scan.check.cls.advice'))
            ->assertSee('Laat jebbari dit oplossen.');
        $html = $response->getContent();
        $this->assertCspClean($html);
        $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $html);
        $this->assertSame('noindex, nofollow', $response->headers->get('X-Robots-Tag'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('/nl/contact?scan=https%3A%2F%2Fbakkerij.example#contact-form', $html);

        // The same report reads in English.
        $this->get('/en/scan/'.$scan->token)->assertOk()->assertSee('Report for bakkerij.example')->assertSee('Fix first');

        // "Let jebbari fix this" opens the contact form with the URL filled in.
        $this->get('/nl/contact?scan=https%3A%2F%2Fbakkerij.example')->assertOk()->assertSee('Ik heb mijn website gecheckt (https://bakkerij.example)', false);
        $this->get('/nl/contact?scan=javascript:alert(1)')->assertOk()->assertDontSee('Ik heb mijn website gecheckt');
    }

    public function test_an_unreachable_site_fails_cleanly(): void
    {
        Mail::fake();
        Http::fake(['*' => Http::failedConnection('cURL error 6: Could not resolve host')]);

        $scan = Scan::create(['url' => 'https://bakkerij.example', 'domain' => 'bakkerij.example', 'email' => 'k@example.com', 'locale' => 'nl', 'ip_hash' => 'x']);
        RunScan::dispatchSync($scan);

        $this->assertSame([Scan::FAILED, 'unreachable'], [$scan->fresh()->status, $scan->fresh()->error]);
        Mail::assertNothingSent();
        $this->get('/nl/scan/'.$scan->token)->assertOk()->assertSee('De check is niet gelukt')->assertSee(__('scan.error.unreachable'));
    }

    public function test_error_pages_and_redirects_to_inside_fail(): void
    {
        Http::fake([
            'https://kapot.example' => Http::response('Not found', 404, ['Content-Type' => 'text/html']),
            'https://doorsturen.example' => Http::response('', 302, ['Location' => 'http://192.168.1.1/']),
        ]);

        $broken = Scan::create(['url' => 'https://kapot.example', 'domain' => 'kapot.example', 'email' => 'k@example.com', 'locale' => 'nl', 'ip_hash' => 'x']);
        $redirect = Scan::create(['url' => 'https://doorsturen.example', 'domain' => 'doorsturen.example', 'email' => 'k@example.com', 'locale' => 'nl', 'ip_hash' => 'x']);
        RunScan::dispatchSync($broken);
        RunScan::dispatchSync($redirect);

        $this->assertSame('status', $broken->fresh()->error);
        $this->assertSame('private', $redirect->fresh()->error);
    }

    public function test_the_status_page_refreshes_while_waiting_and_gives_up_when_stuck(): void
    {
        $scan = Scan::create(['url' => 'https://bakkerij.example', 'domain' => 'bakkerij.example', 'email' => 'k@example.com', 'locale' => 'nl', 'ip_hash' => 'x']);

        $html = $this->get('/nl/scan/'.$scan->token)->assertOk()->assertSee('Je website wordt gecontroleerd')->getContent();
        $this->assertStringContainsString('<meta http-equiv="refresh" content="4">', $html);
        $this->assertCspClean($html);

        $scan->forceFill(['created_at' => now()->subMinutes(30)])->save();
        $html = $this->get('/nl/scan/'.$scan->token)->assertSee(__('scan.error.stuck'))->getContent();
        $this->assertStringNotContainsString('http-equiv="refresh"', $html);

        $this->get('/nl/scan/'.str_repeat('a', 40))->assertNotFound();
    }

    public function test_old_scans_are_deleted(): void
    {
        $old = Scan::create(['url' => 'https://a.example', 'domain' => 'a.example', 'email' => 'a@example.com', 'locale' => 'nl', 'ip_hash' => 'x']);
        $old->forceFill(['created_at' => now()->subDays(91)])->save();
        $new = Scan::create(['url' => 'https://b.example', 'domain' => 'b.example', 'email' => 'b@example.com', 'locale' => 'nl', 'ip_hash' => 'x']);

        $this->artisan('model:prune', ['--model' => [Scan::class]])->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($new);
    }

    public function test_the_scan_pages_are_in_the_sitemap_and_reports_are_not_crawled(): void
    {
        $this->get('/sitemap.xml')->assertSee(url('/nl/scan'), false)->assertSee(url('/en/scan'), false);
        $this->get('/robots.txt')->assertSee('Disallow: /nl/scan/');
    }
}
