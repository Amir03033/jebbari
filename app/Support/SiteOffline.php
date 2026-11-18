<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * The owner's "take the website offline" switch (Admin > Site > Website online).
 * While it is on, every public page answers with a calm offline page (503);
 * the admin keeps working, and signed-in team members still see the site.
 * Stored in the `offline` settings group, so it applies at once.
 */
class SiteOffline
{
    /** Paths that always work: the admin, its assets and the health check. */
    public const ALWAYS_OPEN = ['admin', 'admin/*', 'livewire*', 'filament/*', 'up', 'preview/*', 'storage/*'];

    public static function isOn(): bool
    {
        return (bool) Settings::get('offline.enabled', false);
    }

    /** The owner's own message in the visitor's language, or null for the default text. */
    public static function message(string $locale): ?string
    {
        $text = trim((string) Settings::get("offline.message_$locale", ''));

        return $text !== '' ? $text : null;
    }

    /** When the owner expects the site back, if given and still in the future. */
    public static function backAt(): ?CarbonImmutable
    {
        $value = Settings::get('offline.back_at');

        if (! $value) {
            return null;
        }

        $at = CarbonImmutable::parse($value, config('site.timezone'));

        return $at->isFuture() ? $at : null;
    }

    /** Whether this request is let through: admin paths, and signed-in team members. */
    public static function lets(Request $request): bool
    {
        if ($request->is(...self::ALWAYS_OPEN)) {
            return true;
        }

        return (bool) $request->user()?->canAccessPanel(filament()->getPanel('admin'));
    }
}
