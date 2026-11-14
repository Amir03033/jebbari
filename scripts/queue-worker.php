<?php

/*
 * Processes queued jobs (image resizing for uploads), then stops.
 *
 * For a Plesk scheduled task of the type "Run a PHP script", every minute,
 * created under Websites & Domains > jebbari.nl > Scheduled Tasks so it runs
 * as the site's own system user (never as root):
 *
 *   Script path: /var/www/vhosts/jebbari.nl/httpdocs/scripts/queue-worker.php
 *   PHP version: 8.4
 *
 * It is the same as: php artisan queue:work --stop-when-empty --max-time=55
 * It refuses to run from a web request.
 */

use Illuminate\Foundation\Application;
use Symfony\Component\Console\Input\ArrayInput;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
chdir($root);

define('LARAVEL_START', microtime(true));

require $root.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $root.'/bootstrap/app.php';

exit($app->handleCommand(new ArrayInput([
    'command' => 'queue:work',
    '--stop-when-empty' => true,
    '--max-time' => 55,
    '--tries' => 3,
])));
