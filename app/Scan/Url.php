<?php

namespace App\Scan;

/** Resolves a reference (href, src, Location) against a base URL. */
final class Url
{
    public static function resolve(string $base, string $ref): string
    {
        $ref = trim($ref);

        if ($ref === '' || preg_match('#^[a-z][a-z0-9+.\-]*:#i', $ref)) {
            return $ref === '' ? $base : $ref;
        }

        $b = parse_url($base);
        $origin = ($b['scheme'] ?? 'https').'://'.($b['host'] ?? '').(isset($b['port']) ? ':'.$b['port'] : '');

        if (str_starts_with($ref, '//')) {
            return ($b['scheme'] ?? 'https').':'.$ref;
        }

        if (str_starts_with($ref, '#') || str_starts_with($ref, '?')) {
            return strtok($base, str_starts_with($ref, '#') ? '#' : '?').$ref;
        }

        $path = str_starts_with($ref, '/') ? $ref : preg_replace('#/[^/]*$#', '/', $b['path'] ?? '/').$ref;

        // Remove dot segments.
        [$pathOnly, $rest] = array_pad(preg_split('/(?=[?#])/', $path, 2), 2, '');
        $out = [];
        foreach (explode('/', $pathOnly) as $segment) {
            if ($segment === '..') {
                array_pop($out);
            } elseif ($segment !== '.') {
                $out[] = $segment;
            }
        }

        return $origin.'/'.ltrim(implode('/', $out), '/').$rest;
    }

    public static function origin(string $url): string
    {
        $p = parse_url($url);

        return ($p['scheme'] ?? 'https').'://'.($p['host'] ?? '').(isset($p['port']) ? ':'.$p['port'] : '');
    }
}
