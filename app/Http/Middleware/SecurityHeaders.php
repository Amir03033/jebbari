<?php

namespace App\Http\Middleware;

use App\Content\Site;
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

        // Skip while the Vite dev server is running (it needs a looser policy).
        if (! file_exists(public_path('hot'))) {
            $response->headers->set('Content-Security-Policy', $isAdmin ? self::ADMIN_CSP : self::PUBLIC_CSP.$this->frames());
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'DENY');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($isAdmin || $isPreview) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }

    /** Live demos on case studies may be embedded, and nothing else: only the hosts the owner entered. */
    private function frames(): string
    {
        try {
            $hosts = Site::demoHosts();
        } catch (\Throwable) {
            $hosts = [];
        }

        return $hosts ? '; frame-src '.implode(' ', array_map(fn ($h) => 'https://'.$h, $hosts)) : '';
    }
}
