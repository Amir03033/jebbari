<?php

namespace App\Http\Middleware;

use App\Content\Site;
use App\Scan\Turnstile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public pages get a strict CSP (no inline script, no eval, same-origin only).
 * The admin panel gets a looser one because Filament and Livewire need inline
 * scripts and eval. Admin and preview responses are also marked noindex.
 */
class SecurityHeaders
{
    private const PUBLIC_CSP = "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'";

    private const ADMIN_CSP = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'";

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $isAdmin = $request->is('admin', 'admin/*', 'livewire*', 'livewire-*');
        $isPreview = $request->is('preview/*');
        $isReview = $request->is('review/*');

        // Skip while the Vite dev server is running (it needs a looser policy).
        if (! file_exists(public_path('hot'))) {
            $response->headers->set('Content-Security-Policy', $isAdmin ? self::ADMIN_CSP : ($isReview ? self::PUBLIC_CSP.$this->previewFrame() : $this->publicCsp($request)));
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        // The client's review link carries its secret: never send it on, not even to the preview.
        $response->headers->set('Referrer-Policy', $isReview ? 'no-referrer' : 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'DENY');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($isAdmin || $isPreview || $isReview) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }

    /** The client review page frames the preview subdomain, and nothing else. */
    private function previewFrame(): string
    {
        $parts = parse_url((string) config('preview.url'));

        return isset($parts['scheme'], $parts['host'])
            ? '; frame-src '.$parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '')
            : '';
    }

    /** The strict policy, plus Cloudflare Turnstile on the website check form when it is switched on. */
    private function publicCsp(Request $request): string
    {
        $frames = $this->frameHosts();

        if (Turnstile::enabled() && $request->routeIs('*.scan', '*.price', '*.request.step', '*.appointment.details')) {
            $frames[] = Turnstile::HOST;

            return str_replace("script-src 'self'", "script-src 'self' ".Turnstile::HOST, self::PUBLIC_CSP).'; frame-src '.implode(' ', $frames);
        }

        return self::PUBLIC_CSP.($frames ? '; frame-src '.implode(' ', $frames) : '');
    }

    /** Live demos on case studies may be embedded, and nothing else: only the hosts the owner entered. */
    /** @return list<string> */
    private function frameHosts(): array
    {
        try {
            $hosts = Site::demoHosts();
        } catch (\Throwable) {
            $hosts = [];
        }

        return array_map(fn ($h) => 'https://'.$h, $hosts);
    }
}
