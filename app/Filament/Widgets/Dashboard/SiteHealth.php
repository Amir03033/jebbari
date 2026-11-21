<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Scans\ScanResource;
use App\Models\PageView;
use App\Models\Project;
use App\Models\Scan;
use App\Models\ScanWatch;
use App\Models\Subscriber;
use App\Uptime\Monitor;
use Filament\Widgets\Widget;

/** Most viewed pages, live uptime of the projects, and the website check and newsletter in numbers. */
class SiteHealth extends Widget
{
    protected static ?int $sort = 5;

    protected static bool $isLazy = false;

    protected string $view = 'filament.dashboard.site-health';

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 1];

    protected function getViewData(): array
    {
        $owner = auth()->user()?->isOwner() ?? false;

        return [
            'top' => PageView::top(30, 5)->map(fn ($r) => ['path' => $r->path, 'views' => (int) $r->views])->all(),
            'uptime' => Project::query()->published()->where('monitor', true)->whereNotNull('url')->get()
                ->map(fn (Project $p) => ['name' => loc($p->title) ?? '', 'host' => parse_url((string) $p->url, PHP_URL_HOST), 'summary' => Monitor::summary($p)])
                ->all(),
            'projectsUrl' => ProjectResource::getUrl('index'),
            'numbers' => $owner ? [
                ['label' => 'Website-checks (30 dagen)', 'value' => Scan::query()->where('created_at', '>=', now()->subDays(30))->where('ip_hash', '!=', 'watch')->count(), 'url' => ScanResource::getUrl('index')],
                ['label' => 'Actieve website-watches', 'value' => ScanWatch::query()->whereNotNull('confirmed_at')->count(), 'url' => null],
                ['label' => 'Nieuwsbriefabonnees', 'value' => Subscriber::query()->active()->count(), 'url' => null],
            ] : [],
        ];
    }
}
