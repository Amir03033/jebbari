<?php

namespace App\Filament\Resources\ScanWatches;

use App\Filament\Resources\ScanWatches\Pages\ListScanWatches;
use App\Models\Scan;
use App\Models\ScanWatch;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/** Who asked for a monthly website check (docs/SCAN.md). Read only; deleting stops the watch. */
class ScanWatchResource extends Resource
{
    protected static ?string $model = ScanWatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEye;

    protected static string|\UnitEnum|null $navigationGroup = 'Inbox';

    protected static ?int $navigationSort = 31;

    protected static ?string $modelLabel = 'website-watch';

    protected static ?string $pluralModelLabel = 'website-watches';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('domain')->label('Website')->weight('medium')->searchable()->description(fn (ScanWatch $w) => $w->email),
                TextColumn::make('confirmed_at')->label('Status')->badge()
                    ->state(fn (ScanWatch $w) => $w->confirmed_at ? 'Actief' : 'Wacht op bevestiging')
                    ->color(fn (ScanWatch $w) => $w->confirmed_at ? 'success' : 'gray'),
                TextColumn::make('score')->label('Laatste score')
                    ->state(function (ScanWatch $w) {
                        $s = array_filter(array_intersect_key($w->last_scores ?? [], array_flip(Scan::CATEGORIES)), fn ($v) => $v !== null);

                        return $s ? round(array_sum($s) / count($s)).'/100' : null;
                    })->placeholder('-'),
                TextColumn::make('runs')->label('Checks'),
                TextColumn::make('next_run_at')->label('Volgende check')->date('d-m-Y')->placeholder('-')->sortable(),
                TextColumn::make('created_at')->label('Aangemeld')->since()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([TernaryFilter::make('confirmed_at')->label('Bevestigd')->nullable()])
            ->recordActions([DeleteAction::make()->label('Stoppen')->modalHeading('Watch stoppen en verwijderen?')]);
    }

    public static function getPages(): array
    {
        return ['index' => ListScanWatches::route('/')];
    }
}
