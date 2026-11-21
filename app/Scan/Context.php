<?php

namespace App\Scan;

/** Everything one scan collected, handed to each group of checks. */
final class Context
{
    /**
     * @param  ?Fetched  $httpProbe  the http:// version of the URL, not followed; null when nothing answers there
     * @param  ?array{valid: bool, days_left: ?int}  $certificate
     * @param  ?array  $pagespeed  see PageSpeed::run()
     */
    public function __construct(
        public readonly Fetched $page,
        public readonly Html $html,
        public readonly ?Fetched $httpProbe = null,
        public readonly ?array $certificate = null,
        public readonly ?Fetched $robots = null,
        public readonly bool $sitemap = false,
        public readonly ?array $pagespeed = null,
    ) {}
}
