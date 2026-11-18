<?php

namespace Tests\Feature\Scan;

use App\Scan\Fetcher;
use App\Scan\FetchFailed;
use App\Scan\UnsafeUrl;
use App\Scan\UrlGuard;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UrlGuardTest extends TestCase
{
    private function guard(array $dns = []): UrlGuard
    {
        return new UrlGuard(fn (string $host) => $dns[$host] ?? ['93.184.216.34']);
    }

    private function reason(callable $fn): ?string
    {
        try {
            $fn();
        } catch (UnsafeUrl $e) {
            return $e->reason;
        }

        return null;
    }

    public static function refused(): array
    {
        return [
            'ftp' => ['ftp://example.com/', 'scheme'],
            'file' => ['file:///etc/passwd', 'invalid'],
            'gopher' => ['gopher://example.com/', 'scheme'],
            'credentials' => ['https://user:pass@example.com/', 'credentials'],
            'user only' => ['https://admin@example.com/', 'credentials'],
            'port 8080' => ['http://example.com:8080/', 'port'],
            'port 22' => ['https://example.com:22/', 'port'],
            'localhost' => ['http://localhost/', 'host'],
            'local tld' => ['http://printer.local/', 'host'],
            'loopback' => ['http://127.0.0.1/', 'private'],
            'loopback other' => ['http://127.8.9.10/', 'private'],
            '10/8' => ['http://10.1.2.3/', 'private'],
            '172.16/12' => ['http://172.20.0.1/', 'private'],
            '192.168/16' => ['http://192.168.1.1/', 'private'],
            'metadata' => ['http://169.254.169.254/latest/meta-data/', 'private'],
            'cgnat' => ['http://100.64.0.1/', 'private'],
            'zero' => ['http://0.0.0.0/', 'private'],
            'ipv6 loopback' => ['http://[::1]/', 'private'],
            'ipv6 ula' => ['http://[fd00::1]/', 'private'],
            'ipv6 link local' => ['http://[fe80::1]/', 'private'],
            'ipv4 mapped' => ['http://[::ffff:127.0.0.1]/', 'private'],
            'decimal ip' => ['http://2130706433/', 'host'],
            'no host' => ['https:///path', 'invalid'],
            'whitespace' => ["https://example.com/\npath", 'invalid'],
        ];
    }

    #[DataProvider('refused')]
    public function test_unsafe_urls_are_refused(string $url, string $reason): void
    {
        $this->assertSame($reason, $this->reason(fn () => $this->guard()->check($url)));
    }

    public function test_a_hostname_that_resolves_inside_is_refused(): void
    {
        $guard = $this->guard(['evil.example' => ['127.0.0.1'], 'mixed.example' => ['93.184.216.34', '10.0.0.5'], 'gone.example' => []]);

        $this->assertSame('private', $this->reason(fn () => $guard->check('https://evil.example/')));
        $this->assertSame('private', $this->reason(fn () => $guard->check('https://mixed.example/')), 'any internal address refuses the host');
        $this->assertSame('dns', $this->reason(fn () => $guard->check('https://gone.example/')));
    }

    public function test_public_urls_pass_and_are_normalised(): void
    {
        $target = $this->guard()->check(UrlGuard::normalize('  Example.com/page '));

        $this->assertSame('https://Example.com/page', $target['url']);
        $this->assertSame('example.com', $target['host']);
        $this->assertSame(443, $target['port']);
        $this->assertSame('93.184.216.34', $target['ip']);
        $this->assertSame(80, $this->guard()->check('http://example.com:80/')['port']);
    }

    public function test_every_redirect_is_checked_again(): void
    {
        $this->app->instance(UrlGuard::class, $this->guard(['inside.example' => ['192.168.0.10']]));
        Http::fake([
            'https://example.com/' => Http::response('', 302, ['Location' => 'http://169.254.169.254/latest/meta-data/']),
            'https://example.com/b' => Http::response('', 301, ['Location' => 'https://inside.example/']),
        ]);

        $this->assertSame('private', $this->reason(fn () => app(Fetcher::class)->get('https://example.com/')));
        $this->assertSame('private', $this->reason(fn () => app(Fetcher::class)->get('https://example.com/b')));
        Http::assertNotSent(fn ($r) => str_contains($r->url(), '169.254') || str_contains($r->url(), 'inside.example'));
    }

    public function test_redirects_are_limited_and_the_user_agent_is_clear(): void
    {
        $this->app->instance(UrlGuard::class, $this->guard());
        Http::fake(['*' => Http::response('', 302, ['Location' => '/again'])]);

        try {
            app(Fetcher::class)->get('https://example.com/');
            $this->fail('expected too many redirects');
        } catch (FetchFailed $e) {
            $this->assertSame('redirects', $e->reason);
        }

        Http::assertSentCount(config('scan.max_redirects') + 1);
        Http::assertSent(fn ($r) => $r->header('User-Agent')[0] === config('scan.user_agent'));
    }
}
