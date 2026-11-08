<?php

namespace App\Filament;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Initials avatar drawn locally as an inline SVG. Filament's default provider
 * fetches avatars from ui-avatars.com, which would send every admin's name to a
 * third party on each page view.
 */
class InitialsAvatarProvider implements AvatarProvider
{
    public function get(Model|Authenticatable $record): string
    {
        $initials = collect(preg_split('/\s+/', trim(Filament::getNameForDefaultAvatar($record))))
            ->map(fn (string $part) => mb_strtoupper(mb_substr(preg_replace('/^[^\p{L}\p{N}]+/u', '', $part), 0, 1)))
            ->filter()->take(2)->join('');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" fill="#0E0E0F"/>'
            .'<text x="32" y="32" dy=".35em" text-anchor="middle" font-family="system-ui, sans-serif" font-size="26" fill="#F5F3EE">'
            .e($initials ?: '?').'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
