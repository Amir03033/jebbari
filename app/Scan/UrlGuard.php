<?php

namespace App\Scan;

use Closure;

/**
 * SSRF protection. Every URL the scanner fetches, including each redirect
 * target, passes through check(): http(s) only, ports 80 and 443 only, no
 * credentials, and a host that resolves to public addresses only. The
 * fetcher then connects to the address checked here (no second DNS lookup).
 */
class UrlGuard
{
    /** Ranges that are never fetched, on top of PHP's private and reserved filters. */
    private const BLOCKED = [
        '0.0.0.0/8', '10.0.0.0/8', '100.64.0.0/10', '127.0.0.0/8', '169.254.0.0/16', '172.16.0.0/12',
        '192.0.0.0/24', '192.0.2.0/24', '192.168.0.0/16', '198.18.0.0/15', '198.51.100.0/24',
        '203.0.113.0/24', '224.0.0.0/4', '240.0.0.0/4',
        '::/128', '::1/128', '::ffff:0:0/96', '64:ff9b::/96', '100::/64', '2001:db8::/32', 'fc00::/7', 'fe80::/10', 'ff00::/8',
    ];

    /** @var Closure(string): list<string> */
    private Closure $resolver;

    /** @param  (Closure(string): list<string>)|null  $resolver  host => IP addresses (replaced in tests) */
    public function __construct(?Closure $resolver = null)
    {
        $this->resolver = $resolver ?? self::dns(...);
    }

    /** Adds https:// when the scheme is missing and tidies the input; does not check it. */
    public static function normalize(string $input): string
    {
        $url = trim($input);

        if ($url !== '' && ! preg_match('#^[a-z][a-z0-9+.\-]*://#i', $url)) {
            $url = 'https://'.ltrim($url, '/');
        }

        return $url;
    }

    /**
     * @return array{url: string, scheme: string, host: string, port: int, ip: string}
     *
     * @throws UnsafeUrl
     */
    public function check(string $url): array
    {
        if (strlen($url) > 2048 || preg_match('/[\s\x00-\x1f\x7f]/', $url)) {
            throw new UnsafeUrl('invalid');
        }

        $parts = parse_url($url);

        if ($parts === false || empty($parts['host']) || empty($parts['scheme'])) {
            throw new UnsafeUrl('invalid');
        }

        $scheme = strtolower($parts['scheme']);

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new UnsafeUrl('scheme');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new UnsafeUrl('credentials');
        }

        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        if (! in_array($port, [80, 443], true)) {
            throw new UnsafeUrl('port');
        }

        $host = strtolower(rtrim($parts['host'], '.'));
        $literal = trim($host, '[]');

        if (filter_var($literal, FILTER_VALIDATE_IP)) {
            $ips = [$literal];
        } else {
            if (function_exists('idn_to_ascii') && preg_match('/[^\x20-\x7e]/', $host)) {
                $host = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) ?: $host;
            }

            if (! preg_match('/^(?=.{1,253}$)([a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?\.)+[a-z][a-z0-9\-]{0,62}$/', $host)
                || in_array($host, ['localhost'], true) || preg_match('/\.(localhost|local|internal|lan|home|corp|intranet)$/', $host)) {
                throw new UnsafeUrl('host');
            }

            $ips = ($this->resolver)($host);
        }

        if ($ips === []) {
            throw new UnsafeUrl('dns');
        }

        // Every address must be public: a host that also points inside is refused.
        foreach ($ips as $ip) {
            if (! self::isPublic($ip)) {
                throw new UnsafeUrl('private');
            }
        }

        return ['url' => $url, 'scheme' => $scheme, 'host' => $host, 'port' => $port, 'ip' => $ips[0]];
    }

    public static function isPublic(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }

        foreach (self::BLOCKED as $cidr) {
            if (self::inRange($ip, $cidr)) {
                return false;
            }
        }

        return true;
    }

    private static function inRange(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        $ipBin = inet_pton($ip);
        $subnetBin = inet_pton($subnet);

        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }

        $bits = (int) $bits;
        $bytes = intdiv($bits, 8);

        if (substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
            return false;
        }

        if ($bits % 8 === 0) {
            return true;
        }

        $mask = 0xFF << (8 - $bits % 8) & 0xFF;

        return (ord($ipBin[$bytes]) & $mask) === (ord($subnetBin[$bytes]) & $mask);
    }

    /** @return list<string> */
    private static function dns(string $host): array
    {
        $records = @dns_get_record($host, DNS_A | DNS_AAAA) ?: [];
        $ips = [];

        foreach ($records as $record) {
            $ips[] = $record['ip'] ?? $record['ipv6'] ?? null;
        }

        $ips = array_values(array_unique(array_filter($ips)));

        // Fall back to the system resolver (follows CNAMEs the same way the fetch would).
        return $ips ?: (@gethostbynamel($host) ?: []);
    }
}
