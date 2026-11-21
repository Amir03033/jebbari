<?php

/*
 * Preview deploy: one client website at a time on its own subdomain.
 * Guide: docs/PREVIEW-DEPLOY.md. Everything comes from Jebbari's .env.
 */
return [
    // The subdomain's httpdocs folder (its document root is httpdocs/public).
    'path' => env('PREVIEW_PATH'),

    // Where the preview is served.
    'url' => rtrim((string) env('PREVIEW_URL', 'https://preview.jebbari.nl'), '/'),

    // The access cookie is set by jebbari.nl for this domain, so the subdomain receives it.
    'cookie' => 'jebbari_preview',
    'cookie_domain' => env('PREVIEW_COOKIE_DOMAIN', '.jebbari.nl'),

    // Tools on the server. PHP must match the subdomain's PHP version (8.2+).
    'php' => env('PREVIEW_PHP', 'php'),
    'composer' => env('PREVIEW_COMPOSER'),
    'npm' => env('PREVIEW_NPM'),

    // Fine-grained GitHub token, read-only (Contents: read), for private repositories.
    'github_token' => env('PREVIEW_GITHUB_TOKEN'),

    // The empty database of the preview site. Never Jebbari's own.
    'db' => [
        'host' => env('PREVIEW_DB_HOST', '127.0.0.1'),
        'port' => env('PREVIEW_DB_PORT', '3306'),
        'database' => env('PREVIEW_DB_DATABASE'),
        'username' => env('PREVIEW_DB_USERNAME'),
        'password' => env('PREVIEW_DB_PASSWORD'),
    ],

    // A new preview link stays valid this many days (editable per preview).
    'lifetime_days' => (int) env('PREVIEW_LIFETIME_DAYS', 14),

    // Maximum build time in seconds. The 'previews' queue's retry_after is set above it.
    'timeout' => (int) env('PREVIEW_TIMEOUT', 1500),

    'script' => base_path('scripts/preview-deploy.sh'),
    'env_template' => resource_path('preview/env.template'),
];
