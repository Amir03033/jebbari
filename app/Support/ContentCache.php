<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/** A version counter that changes on every publish, so cached public content can be keyed by it. */
class ContentCache
{
    public static function version(): string
    {
        return (string) Cache::rememberForever('content.version', fn () => (string) now()->getTimestampMs());
    }

    public static function bump(): void
    {
        Cache::forever('content.version', (string) now()->getTimestampMs());
    }
}
