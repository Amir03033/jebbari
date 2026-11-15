<?php

namespace App\Scan;

/** One fetched URL, after redirects. Header names are lower case. */
final class Fetched
{
    /**
     * @param  array<string, list<string>>  $headers
     * @param  list<string>  $redirects  every URL visited before the final one
     */
    public function __construct(
        public readonly string $url,
        public readonly int $status,
        public readonly array $headers,
        public readonly string $body,
        public readonly array $redirects = [],
        public readonly int $ms = 0,
        public readonly bool $truncated = false,
        public readonly bool $tlsError = false,
    ) {}

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)][0] ?? null;
    }

    /** @return list<string> */
    public function headerAll(string $name): array
    {
        return $this->headers[strtolower($name)] ?? [];
    }

    public function ok(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function isHttps(): bool
    {
        return str_starts_with(strtolower($this->url), 'https://');
    }
}
