<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Site settings stored as one JSON row per group (company, contact, social,
 * header, footer, seo, analytics). Falls back to config/site.php until filled.
 */
class Settings
{
    public static function group(string $group): array
    {
        return Cache::rememberForever("settings.$group", fn () => Setting::query()->where('key', $group)->value('value') ?? []);
    }

    public static function get(string $path, mixed $default = null): mixed
    {
        [$group, $key] = array_pad(explode('.', $path, 2), 2, null);

        return data_get(self::group($group), $key, $default);
    }

    public static function save(string $group, array $value): void
    {
        Setting::query()->updateOrCreate(['key' => $group], ['value' => $value]);
        Cache::forget("settings.$group");
        ContentCache::bump();
    }
}
