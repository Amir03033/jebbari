<?php

namespace App\Scan;

use GuzzleHttp\Exception\TransferException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Fetches a URL for the scanner: every hop (redirects included) is checked by
 * the UrlGuard, the connection is pinned to the address that was checked
 * (CURLOPT_RESOLVE, so DNS cannot change in between), with a timeout, a size
 * limit, a redirect limit and a clear user agent.
 */
class Fetcher
{
    private int $maxBytes = 0;

    private bool $tooLarge = false;

    public function __construct(private UrlGuard $guard) {}

    /**
     * @throws UnsafeUrl when this URL or a redirect target is not allowed
     * @throws FetchFailed when the site cannot be reached
     */
    public function get(string $url, ?int $maxRedirects = null, ?int $maxBytes = null): Fetched
    {
        $maxRedirects ??= (int) config('scan.max_redirects');
        $maxBytes ??= (int) config('scan.max_bytes');
        $this->maxBytes = $maxBytes;
        $visited = [];
        $started = hrtime(true);

        for ($hop = 0; ; $hop++) {
            $target = $this->guard->check($url);
            [$response, $tlsError] = $this->request($target);

            $location = $response->header('Location');

            if ($response->status() >= 300 && $response->status() < 400 && $location !== '') {
                if ($hop >= $maxRedirects) {
                    if ($maxRedirects === 0) {
                        return $this->result($url, $response, $visited, $started, $maxBytes, $tlsError);
                    }

                    throw new FetchFailed('redirects');
                }

                $visited[] = $url;
                $url = Url::resolve($url, $location);

                continue;
            }

            return $this->result($url, $response, $visited, $started, $maxBytes, $tlsError);
        }
    }

    /** @return array{0: Response, 1: bool} */
    private function request(array $target): array
    {
        $ip = str_contains($target['ip'], ':') ? '['.$target['ip'].']' : $target['ip'];

        $max = $this->maxBytes;
        $this->tooLarge = false;

        $client = fn (bool $verify) => Http::withOptions([
            'allow_redirects' => false,
            'verify' => $verify,
            // Pin the connection to the address the guard approved, so DNS cannot change in between.
            'curl' => [CURLOPT_RESOLVE => [$target['host'].':'.$target['port'].':'.$ip]],
            // Returning true aborts the transfer once the size limit is passed.
            'progress' => function ($total, $now) use ($max) {
                return $this->tooLarge = $total > $max || $now > $max;
            },
        ])
            ->withUserAgent(config('scan.user_agent'))
            ->accept('text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8')
            ->timeout((int) config('scan.timeout'))
            ->connectTimeout((int) config('scan.connect_timeout'));

        try {
            return [$client(true)->get($target['url']), false];
        } catch (ConnectionException|TransferException $e) {
            // An invalid certificate is a finding, not a dead end: read the page anyway and report it.
            if ($target['scheme'] === 'https' && preg_match('/SSL|certificate|TLS/i', $e->getMessage())) {
                try {
                    return [$client(false)->get($target['url']), true];
                } catch (ConnectionException|TransferException $e2) {
                    throw new FetchFailed($this->reason($e2), $e2);
                }
            }

            throw new FetchFailed($this->reason($e), $e);
        }
    }

    private function reason(\Throwable $e): string
    {
        return match (true) {
            $this->tooLarge => 'too_large',
            (bool) preg_match('/timed out|timeout/i', $e->getMessage()) => 'timeout',
            default => 'unreachable',
        };
    }

    private function result(string $url, Response $response, array $visited, int $started, int $maxBytes, bool $tlsError): Fetched
    {
        $body = $response->body();
        $truncated = strlen($body) > $maxBytes;

        if ($truncated) {
            $body = substr($body, 0, $maxBytes);
        }

        $headers = [];
        foreach ($response->headers() as $name => $values) {
            $headers[strtolower($name)] = array_values((array) $values);
        }

        return new Fetched(
            url: $url,
            status: $response->status(),
            headers: $headers,
            body: $body,
            redirects: $visited,
            ms: (int) ((hrtime(true) - $started) / 1_000_000),
            truncated: $truncated,
            tlsError: $tlsError,
        );
    }
}
