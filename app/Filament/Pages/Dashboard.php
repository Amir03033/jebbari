<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Dashboard\Attention;
use App\Filament\Widgets\Dashboard\KeyNumbers;
use App\Filament\Widgets\Dashboard\SiteHealth;
use App\Filament\Widgets\Dashboard\Upcoming;
use App\Filament\Widgets\Dashboard\VisitorsChart;
use App\Filament\Widgets\Dashboard\Welcome;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * The start screen: what needs attention first, then the numbers, then the
 * site itself. Every number is real and links to where you can act on it.
 */
class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    public function getColumns(): int|array
    {
        return ['default' => 1, 'lg' => 3];
    }

    public function getWidgets(): array
    {
        return [Welcome::class, KeyNumbers::class, Attention::class, Upcoming::class, VisitorsChart::class, SiteHealth::class];
    }
}
