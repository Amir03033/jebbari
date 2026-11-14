<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Cookieless statistics: a counter per day and path. Nothing about the visitor
 * is stored (no IP address, no identifier, no cookie), so no consent is needed.
 */
class PageView extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public static function record(string $path): void
    {
        $date = now(config('site.timezone'))->toDateString();

        self::query()->upsert([['date' => $date, 'path' => mb_substr($path, 0, 190), 'views' => 1]], ['date', 'path'], ['views' => DB::raw('views + 1')]);
    }

    public static function totalSince(int $days): int
    {
        return (int) self::query()->where('date', '>=', now(config('site.timezone'))->subDays($days - 1)->toDateString())->sum('views');
    }

    /** @return Collection<int, object{path: string, views: int}> */
    public static function top(int $days = 30, int $limit = 5): Collection
    {
        return self::query()->where('date', '>=', now(config('site.timezone'))->subDays($days - 1)->toDateString())
            ->selectRaw('path, SUM(views) as views')->groupBy('path')->orderByDesc('views')->limit($limit)->get();
    }
}
