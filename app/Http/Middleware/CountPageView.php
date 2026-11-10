<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Counts a view for successful public page loads. Skips bots, signed-in
 * editors, prefetches and visitors who send Do Not Track or Global Privacy
 * Control. Counting must never break a page, so failures are only reported.
 */
class CountPageView
{
    private const BOTS = '/bot|crawl|spider|slurp|preview|monitor|curl|wget|python|headless|lighthouse|pingdom|uptime/i';

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! $this->countable($request, $response)) {
            return;
        }

        try {
            PageView::record('/'.ltrim($request->path(), '/'));
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function countable(Request $request, Response $response): bool
    {
        $agent = (string) $request->userAgent();

        return $request->isMethod('GET')
            && $response->getStatusCode() === 200
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            && $agent !== ''
            && ! preg_match(self::BOTS, $agent)
            && $request->header('DNT') !== '1'
            && $request->header('Sec-GPC') !== '1'
            && ! in_array($request->header('Sec-Purpose', $request->header('Purpose')), ['prefetch', 'prerender'], true)
            && ! $request->user();
    }
}
