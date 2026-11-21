<?php

/*
 * The free website check (/nl/scan, /en/scan). See docs/SCAN.md.
 * Keys and secrets come from .env only.
 */
return [
    // Sent with every request, so site owners can see who visited.
    'user_agent' => 'JebbariScan/1.0 (+https://jebbari.nl/nl/scan)',

    // Fetching the page that is checked (SSRF rules: app/Scan/UrlGuard.php).
    'timeout' => 10,
    'connect_timeout' => 5,
    'max_bytes' => 2 * 1024 * 1024,
    'max_redirects' => 4,

    // Scans per visitor (keyed IP hash) per 24 hours.
    'daily_limit' => (int) env('SCAN_DAILY_LIMIT', 3),

    // Scans, e-mail addresses included, are deleted after this many days.
    'retention_days' => 90,

    // Google PageSpeed Insights (mobile). Works without a key at a low quota.
    'pagespeed' => [
        'enabled' => (bool) env('PAGESPEED_ENABLED', true),
        'key' => env('PAGESPEED_API_KEY'),
        'timeout' => 50,
    ],

    // Cloudflare Turnstile. Off until both keys are set; honeypot and timing always apply.
    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],
];
