<?php

namespace App\Uptime;

use App\Models\Project;
use App\Models\UptimeCheck;
use App\Scan\Fetcher;
use App\Scan\FetchFailed;
use App\Scan\UnsafeUrl;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Live uptime for published projects that have "measure live" on: one request
 * to the project's address every few minutes, through the same SSRF-safe
 * fetcher as the website check. Nothing is estimated; the case study shows
 * only what was measured, and only after enough measurements (docs/UPTIME.md).
 */
class Monitor
{
    /** Below this many measurements in the window, nothing is shown. */
    public const MIN_CHECKS = 24;

    public const WINDOW_DAYS = 30;

    /** The last check counts as "online now" when it is this recent. */
    public const FRESH_MINUTES = 90;

    public function __construct(private Fetcher $fetcher) {}

    /** Measure every monitored project once. Returns how many were checked. */
    public function checkAll(): int
    {
        $projects = Project::query()->published()->where('monitor', true)->whereNotNull('url')->get();

        foreach ($projects as $project) {
            $this->check($project);
        }

        return $projects->count();
    }

    public function check(Project $project): UptimeCheck
    {
        try {
            $page = $this->fetcher->get($project->url, null, 256 * 1024);
            $ok = $page->status >= 200 && $page->status < 400;
            $row = ['ok' => $ok, 'status' => $page->status, 'ms' => $page->ms];
        } catch (FetchFailed|UnsafeUrl) {
            $row = ['ok' => false, 'status' => null, 'ms' => null];
        } catch (Throwable $e) {
            report($e);
            $row = ['ok' => false, 'status' => null, 'ms' => null];
        }

        Cache::forget(self::cacheKey($project->id));

        return UptimeCheck::create($row + ['project_id' => $project->id, 'checked_at' => now()]);
    }

    /**
     * What a case study shows, or null when there is not enough data yet.
     *
     * @return array{uptime: float, ms: ?int, checks: int, online: ?bool, since: string}|null
     */
    public static function summary(Project $project): ?array
    {
        if (! $project->monitor || ! $project->url) {
            return null;
        }

        return Cache::remember(self::cacheKey($project->id), 600, function () use ($project) {
            $from = now()->subDays(self::WINDOW_DAYS);
            $base = UptimeCheck::query()->where('project_id', $project->id)->where('checked_at', '>=', $from);

            $checks = (clone $base)->count();

            if ($checks < self::MIN_CHECKS) {
                return null;
            }

            $ok = (clone $base)->where('ok', true)->count();
            $ms = (clone $base)->where('ok', true)->avg('ms');
            $first = (clone $base)->min('checked_at');
            // "Online now" after a good last check; "not reachable" only after two failed checks in a row, so one blip is not news.
            $recent = UptimeCheck::query()->where('project_id', $project->id)->latest('checked_at')->limit(2)->get();
            $last = $recent->first();
            $fresh = $last && $last->checked_at->gt(now()->subMinutes(self::FRESH_MINUTES));
            $online = ! $fresh ? null : ($last->ok ? true : ($recent->count() === 2 && ! $recent->last()->ok ? false : null));

            return [
                'uptime' => round($ok / $checks * 100, 2),
                'ms' => $ms !== null ? (int) round($ms) : null,
                'checks' => $checks,
                'online' => $online,
                'since' => (string) $first,
            ];
        });
    }

    private static function cacheKey(int $id): string
    {
        return "uptime.summary.$id";
    }
}
