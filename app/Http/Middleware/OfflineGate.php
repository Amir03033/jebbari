<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use App\Support\SiteOffline;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * While the owner has taken the website offline, visitors get the offline page
 * (503, with Retry-After so search engines keep the pages). The admin and
 * signed-in team members are let through; see App\Support\SiteOffline.
 */
class OfflineGate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! SiteOffline::isOn() || SiteOffline::lets($request)) {
            return $next($request);
        }

        $segment = $request->segment(1);
        $locale = in_array($segment, Locale::all(), true) ? $segment : Locale::preferred($request);
        app()->setLocale($locale);

        $back = SiteOffline::backAt();
        $retry = $back ? max(60, (int) now()->diffInSeconds($back)) : 3600;

        return response()
            ->view('errors.offline', [
                'locale' => $locale,
                'message' => SiteOffline::message($locale),
                'back' => $back,
            ], 503)
            ->header('Retry-After', (string) $retry)
            ->header('X-Robots-Tag', 'noindex')
            ->header('Cache-Control', 'no-store');
    }
}
