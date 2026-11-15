<?php

namespace App\Filament\Resources\AvailabilityRules;

use App\Filament\Resources\AvailabilityRules\Pages\CreateAvailabilityRule;
use App\Filament\Resources\AvailabilityRules\Pages\EditAvailabilityRule;
use App\Filament\Resources\AvailabilityRules\Pages\ListAvailabilityRules;
use App\Models\AppointmentType;
use App\Models\AvailabilityRule;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** When appointments can be booked: per weekday, in Dutch time. */
class AvailabilityRuleResource extends Resource
{
    protected static ?string $model = AvailabilityRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|\UnitEnum|null $navigationGroup = 'Prijzen & afspraken';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'beschikbaarheid';

    protected static ?string $pluralModelLabel = 'beschikbaarheid';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Beschikbaar')->description('Nederlandse tijd. Meerdere blokken per dag mag (bijvoorbeeld ochtend en middag).')
                ->columns(['default' => 1, 'md' => 2])->schema([
                    Select::make('weekday')->label('Dag')->options(AvailabilityRule::WEEKDAYS)->required(),
                    Select::make('appointment_type_id')->label('Alleen voor')->placeholder('Alle gesprekstypes')
                        ->options(fn () => AppointmentType::query()->orderBy('sort')->get()->mapWithKeys(fn ($t) => [$t->id => loc($t->name, 'nl')])),
                    TimePicker::make('starts_at')->label('Van')->seconds(false)->required(),
                    TimePicker::make('ends_at')->label('Tot')->seconds(false)->required()->after('starts_at'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('weekday')->label('Dag')->formatStateUsing(fn ($state) => AvailabilityRule::WEEKDAYS[$state] ?? $state)->sortable(),
                TextColumn::make('starts_at')->label('Van')->time('H:i'),
                TextColumn::make('ends_at')->label('Tot')->time('H:i'),
                TextColumn::make('appointmentType.name.nl')->label('Alleen voor')->placeholder('Alle gesprekstypes'),
            ])
            ->defaultSort('weekday')
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListAvailabilityRules::route('/'), 'create' => CreateAvailabilityRule::route('/create'), 'edit' => EditAvailabilityRule::route('/{record}/edit')];
    }
}
