<?php

namespace App\Scan;

use Illuminate\Support\Facades\Http;
use Throwable;

/** Cloudflare Turnstile. Off until both keys are set in .env. */
class Turnstile
{
    public const HOST = 'https://challenges.cloudflare.com';

    public static function enabled(): bool
    {
        return filled(config('scan.turnstile.site_key')) && filled(config('scan.turnstile.secret_key'));
    }

    public static function siteKey(): ?string
    {
        return config('scan.turnstile.site_key');
    }

    public function verify(?string $token, ?string $ip): bool
    {
        if (! self::enabled()) {
            return true;
        }

        if (blank($token)) {
            return false;
        }

        try {
            return (bool) Http::asForm()->timeout(8)->post(self::HOST.'/turnstile/v0/siteverify', [
                'secret' => config('scan.turnstile.secret_key'),
                'response' => $token,
                'remoteip' => $ip,
            ])->json('success', false);
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
