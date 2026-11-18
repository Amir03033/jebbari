<?php

namespace App\Scan\Checks;

use App\Scan\Check;
use App\Scan\Context;
use App\Scan\Url;

class Security
{
    /** 180 days, the usual minimum for HSTS. */
    private const HSTS_MIN = 15552000;

    public static function run(Context $c): array
    {
        $https = $c->page->isHttps();
        $checks = [Check::make('https', $https ? Check::GOOD : Check::BAD)];

        $checks[] = self::redirect($c);

        if ($https) {
            $checks[] = self::certificate($c);
        }

        $checks[] = self::hsts($c, $https);
        $checks[] = self::csp($c);
        $checks[] = self::frames($c);
        $checks[] = self::nosniff($c);
        $checks[] = Check::make('referrer_policy', filled($c->page->header('referrer-policy')) ? Check::GOOD : Check::WARNING);

        if ($https) {
            $checks[] = self::mixedContent($c);
        }

        $checks[] = self::cookies($c, $https);

        return $checks;
    }

    private static function redirect(Context $c): array
    {
        $probe = $c->httpProbe;

        if ($probe === null) {
            // Nothing answers on http://, so nobody can end up on an insecure version.
            return Check::make('http_redirect', $c->page->isHttps() ? Check::GOOD : Check::SKIPPED, $c->page->isHttps() ? 'closed' : 'skipped');
        }

        $location = (string) $probe->header('location');
        $target = $location !== '' ? Url::resolve($probe->url, $location) : '';

        if ($probe->status >= 300 && $probe->status < 400 && str_starts_with(strtolower($target), 'https://')) {
            return Check::make('http_redirect', Check::GOOD);
        }

        return Check::make('http_redirect', Check::BAD);
    }

    private static function certificate(Context $c): array
    {
        $cert = $c->certificate;

        if ($c->page->tlsError || ($cert !== null && ! $cert['valid'])) {
            return Check::make('certificate', Check::BAD, 'invalid');
        }

        if ($cert === null || $cert['days_left'] === null) {
            return Check::make('certificate', Check::SKIPPED);
        }

        $days = $cert['days_left'];

        return match (true) {
            $days < 0 => Check::make('certificate', Check::BAD, 'expired'),
            $days < 14 => Check::make('certificate', Check::WARNING, 'expiring', ['days' => $days]),
            default => Check::make('certificate', Check::GOOD, null, ['days' => $days]),
        };
    }

    private static function hsts(Context $c, bool $https): array
    {
        $value = (string) $c->page->header('strict-transport-security');

        if (! $https || $value === '') {
            return Check::make('hsts', Check::BAD);
        }

        $age = preg_match('/max-age\s*=\s*"?(\d+)/i', $value, $m) ? (int) $m[1] : 0;

        return $age >= self::HSTS_MIN ? Check::make('hsts', Check::GOOD) : Check::make('hsts', Check::WARNING, 'short', ['days' => intdiv($age, 86400)]);
    }

    private static function csp(Context $c): array
    {
        $policy = (string) $c->page->header('content-security-policy');

        if ($policy === '') {
            return Check::make('csp', Check::WARNING);
        }

        $script = preg_match('/(?:^|;)\s*script-src\s+([^;]*)/i', $policy, $m) ? $m[1]
            : (preg_match('/(?:^|;)\s*default-src\s+([^;]*)/i', $policy, $d) ? $d[1] : '');

        return str_contains($script, "'unsafe-inline'") && ! preg_match("/'(nonce-|sha256-|sha384-|sha512-|strict-dynamic)/", $script)
            ? Check::make('csp', Check::WARNING, 'weak')
            : Check::make('csp', Check::GOOD);
    }

    private static function frames(Context $c): array
    {
        $xfo = strtolower((string) $c->page->header('x-frame-options'));
        $csp = strtolower((string) $c->page->header('content-security-policy'));

        return in_array($xfo, ['deny', 'sameorigin'], true) || str_contains($csp, 'frame-ancestors')
            ? Check::make('x_frame_options', Check::GOOD)
            : Check::make('x_frame_options', Check::WARNING);
    }

    private static function nosniff(Context $c): array
    {
        return strtolower(trim((string) $c->page->header('x-content-type-options'))) === 'nosniff'
            ? Check::make('x_content_type_options', Check::GOOD)
            : Check::make('x_content_type_options', Check::WARNING);
    }

    private static function mixedContent(Context $c): array
    {
        $active = 0;
        $passive = 0;
        $insecure = fn (string $v) => str_starts_with(strtolower(trim($v)), 'http://');

        foreach ($c->html->all('//script[@src] | //iframe[@src] | //link[@href] | //object[@data] | //embed[@src]') as $el) {
            $value = $el->getAttribute($el->hasAttribute('src') ? 'src' : ($el->hasAttribute('data') ? 'data' : 'href'));

            if ($el->tagName === 'link' && ! preg_match('/\b(stylesheet|preload|modulepreload|icon)\b/i', $el->getAttribute('rel'))) {
                continue;
            }

            $active += $insecure($value) ? 1 : 0;
        }

        foreach ($c->html->all('//img | //source | //video | //audio') as $el) {
            $values = [$el->getAttribute('src'), ...preg_split('/\s*,\s*/', $el->getAttribute('srcset'))];
            $passive += count(array_filter($values, $insecure)) > 0 ? 1 : 0;
        }

        return match (true) {
            $active > 0 => Check::make('mixed_content', Check::BAD, null, ['count' => $active + $passive]),
            $passive > 0 => Check::make('mixed_content', Check::WARNING, null, ['count' => $passive]),
            default => Check::make('mixed_content', Check::GOOD),
        };
    }

    private static function cookies(Context $c, bool $https): array
    {
        $cookies = $c->page->headerAll('set-cookie');

        if ($cookies === []) {
            return Check::make('cookies', Check::GOOD, 'none');
        }

        $weak = [];
        foreach ($cookies as $cookie) {
            $name = mb_substr(trim((string) strtok($cookie, '=')), 0, 40);
            $flags = strtolower($cookie);
            $missing = array_filter([
                $https && ! preg_match('/;\s*secure\b/', $flags) ? 'Secure' : null,
                ! preg_match('/;\s*httponly\b/', $flags) ? 'HttpOnly' : null,
                ! preg_match('/;\s*samesite\s*=/', $flags) ? 'SameSite' : null,
            ]);

            if ($missing) {
                $weak[] = $name.' ('.implode(', ', $missing).')';
            }
        }

        return $weak === []
            ? Check::make('cookies', Check::GOOD, null, ['count' => count($cookies)])
            : Check::make('cookies', Check::WARNING, null, ['list' => implode('; ', array_slice($weak, 0, 5))]);
    }
}
