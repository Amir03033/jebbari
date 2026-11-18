<?php

namespace App\Filament\Resources\PriceItems;

use App\Filament\Resources\PriceItems\Pages\CreatePriceItem;
use App\Filament\Resources\PriceItems\Pages\EditPriceItem;
use App\Filament\Resources\PriceItems\Pages\ListPriceItems;
use App\Filament\Support\Translated;
use App\Leads\PriceCalculator;
use App\Models\PriceItem;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** The calculator's prices: changed here, live without a deploy. */
class PriceItemResource extends Resource
{
    protected static ?string $model = PriceItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static string|\UnitEnum|null $navigationGroup = 'Prijzen & afspraken';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'prijsregel';

    protected static ?string $pluralModelLabel = 'prijzen (calculator)';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Prijsregel')->columns(['default' => 1, 'md' => 2])->schema([
                Select::make('group')->label('Stap in de calculator')->options(PriceItem::GROUPS)->required()
                    ->helperText('Projecttype: één keuze, verplicht. Omvang en deadline: één keuze. Extra: meerdere.'),
                TextInput::make('key')->label('Sleutel')->required()->maxLength(60)->alphaDash()->unique(ignoreRecord: true)
                    ->helperText('Korte code zonder spaties, bijvoorbeeld "website" of "meertalig". Niet meer wijzigen als hij gebruikt wordt.'),
                TextInput::make('price_min')->label('Prijs vanaf (€, hele euro\'s)')->numeric()->minValue(0)->maxValue(10000000)
                    ->requiredIf('is_active', true),
                TextInput::make('price_max')->label('Prijs tot (€, hele euro\'s)')->numeric()->minValue(0)->maxValue(10000000)
                    ->requiredIf('is_active', true)->gte('price_min'),
                TextInput::make('sort')->label('Volgorde')->numeric()->default(0),
                Toggle::make('is_active')->label('Zichtbaar in de calculator')->helperText('Alleen met beide prijzen ingevuld.'),
            ]),
            Section::make('Tekst')->schema([
                Translated::text('name', 'Naam', true, 80),
                Translated::textarea('description', 'Korte uitleg', false, 2),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name.nl')->label('Naam')->weight('medium')->description(fn (PriceItem $r) => $r->key),
                TextColumn::make('group')->label('Stap')->badge()->formatStateUsing(fn ($state) => PriceItem::GROUPS[$state] ?? $state),
                TextColumn::make('range')->label('Prijs')->state(fn (PriceItem $r) => PriceCalculator::range($r->price_min, $r->price_max))->placeholder('Nog niet ingevuld'),
                IconColumn::make('is_active')->label('Zichtbaar')->boolean(),
                TextColumn::make('sort')->label('#')->sortable(),
            ])
            ->defaultSort('sort')
            ->filters([SelectFilter::make('group')->label('Stap')->options(PriceItem::GROUPS)])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListPriceItems::route('/'), 'create' => CreatePriceItem::route('/create'), 'edit' => EditPriceItem::route('/{record}/edit')];
    }
}
