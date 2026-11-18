<?php

namespace App\Filament\Resources\Scans;

use App\Filament\Resources\Scans\Pages\ListScans;
use App\Models\Scan;
use App\Support\Locale;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/** Website checks and the leads among them. Read-only apart from deleting one on request. */
class ScanResource extends Resource
{
    protected static ?string $model = Scan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static string|\UnitEnum|null $navigationGroup = 'Inbox';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'website-check';

    protected static ?string $pluralModelLabel = 'website-checks';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('domain')->label('Domein')->searchable()->description(fn (Scan $r) => mb_strimwidth($r->url, 0, 70, '…')),
                TextColumn::make('email')->label('E-mail')->searchable()->copyable(),
                IconColumn::make('contact_consent')->label('Lead')->boolean()->tooltip('Mag contact opnemen'),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (string $state, Scan $r) => match ($state) {
                        Scan::DONE => 'Klaar', Scan::FAILED => 'Mislukt'.($r->error ? ' ('.$r->error.')' : ''), Scan::RUNNING => 'Bezig', default => 'Wachtrij',
                    })
                    ->color(fn (string $state) => match ($state) {
                        Scan::DONE => 'success', Scan::FAILED => 'danger', default => 'warning',
                    }),
                TextColumn::make('overall')->label('Totaal')->state(fn (Scan $r) => $r->overall())->numeric(),
                ...array_map(fn ($c) => TextColumn::make('scores.'.$c)->label(match ($c) {
                    'security' => 'Beveiliging', 'seo' => 'SEO', 'speed' => 'Snelheid', default => 'Toegankelijkheid',
                })->toggleable(isToggledHiddenByDefault: true), Scan::CATEGORIES),
                TextColumn::make('locale')->label('Taal')->formatStateUsing(fn ($state) => strtoupper($state))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Aangevraagd')->dateTime('d-m-Y H:i')->timezone('Europe/Amsterdam')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('contact_consent')->label('Lead (mag contact opnemen)'),
                SelectFilter::make('status')->options([Scan::DONE => 'Klaar', Scan::FAILED => 'Mislukt', Scan::PENDING => 'Wachtrij', Scan::RUNNING => 'Bezig']),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('report')->label('Rapport')->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Scan $r) => Locale::route('scan.show', ['scan' => $r->token], 'nl'), shouldOpenInNewTab: true),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListScans::route('/')];
    }
}
