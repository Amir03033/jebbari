<?php

namespace App\Filament\Widgets;

use App\Models\PageView;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Page views counted by the site itself: no cookies, no visitor data, no third party. */
class VisitorStats extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Bezoek (zonder cookies gemeten)';

    protected function getStats(): array
    {
        $top = PageView::top(30, 3);

        return [
            Stat::make('Vandaag', PageView::totalSince(1))->description('Paginaweergaven'),
            Stat::make('Laatste 7 dagen', PageView::totalSince(7))->description('Paginaweergaven'),
            Stat::make('Laatste 30 dagen', PageView::totalSince(30))->description('Paginaweergaven'),
            Stat::make('Meest bekeken (30 dagen)', $top->first()->path ?? '—')
                ->description($top->skip(1)->map(fn ($row) => $row->path.' ('.$row->views.')')->implode(' · ') ?: 'Nog geen gegevens'),
        ];
    }
}
