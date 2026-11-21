<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\PageView;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

/** Page views per day, counted by the site itself (no cookies, no visitor data). */
class VisitorsChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Bezoek';

    protected ?string $description = 'Paginaweergaven per dag, zonder cookies gemeten';

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 2];

    protected ?string $maxHeight = '260px';

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return ['7' => '7 dagen', '30' => '30 dagen', '90' => '90 dagen'];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $tz = config('site.timezone');
        $n = (int) ($this->filter ?: 30);
        $days = collect(range($n - 1, 0))->map(fn ($i) => CarbonImmutable::now($tz)->subDays($i));
        $views = PageView::query()->where('date', '>=', $days->first()->toDateString())->selectRaw('date, SUM(views) as v')->groupBy('date')->pluck('v', 'date')
            ->mapWithKeys(fn ($v, $d) => [substr((string) $d, 0, 10) => (int) $v]);

        return [
            'datasets' => [[
                'label' => 'Weergaven',
                'data' => $days->map(fn ($d) => (int) ($views[$d->toDateString()] ?? 0))->all(),
                'fill' => true,
                'tension' => 0.35,
                'pointRadius' => 0,
                'borderWidth' => 2,
                // The site's cobalt accent, readable on the light and the dark admin.
                'borderColor' => '#5b78ff',
                'backgroundColor' => 'rgba(91, 120, 255, 0.14)',
            ]],
            'labels' => $days->map(fn ($d) => $d->locale('nl')->translatedFormat('j M'))->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]], 'x' => ['grid' => ['display' => false]]],
        ];
    }
}
