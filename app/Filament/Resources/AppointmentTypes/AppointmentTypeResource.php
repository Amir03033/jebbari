<?php

namespace App\Filament\Resources\AppointmentTypes;

use App\Filament\Resources\AppointmentTypes\Pages\CreateAppointmentType;
use App\Filament\Resources\AppointmentTypes\Pages\EditAppointmentType;
use App\Filament\Resources\AppointmentTypes\Pages\ListAppointmentTypes;
use App\Filament\Support\Translated;
use App\Models\AppointmentType;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Kinds of meeting visitors can book (online, at a place), each with its own length. */
class AppointmentTypeResource extends Resource
{
    protected static ?string $model = AppointmentType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|\UnitEnum|null $navigationGroup = 'Prijzen & afspraken';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'gesprekstype';

    protected static ?string $pluralModelLabel = 'gesprekstypes';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Gesprek')->columns(['default' => 1, 'md' => 2])->schema([
                TextInput::make('duration_minutes')->label('Duur (minuten)')->numeric()->required()->minValue(15)->maxValue(240)->default(30),
                TextInput::make('sort')->label('Volgorde')->numeric()->default(0),
                Toggle::make('is_online')->label('Online (videobellen)'),
                Toggle::make('is_active')->label('Boekbaar'),
            ]),
            Section::make('Tekst')->schema([
                Translated::text('name', 'Naam', true, 80),
                Translated::text('location', 'Locatie (adres of "Online")', false, 160),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name.nl')->label('Naam')->weight('medium'),
                TextColumn::make('duration_minutes')->label('Duur')->suffix(' min'),
                TextColumn::make('location.nl')->label('Locatie')->placeholder('—'),
                IconColumn::make('is_active')->label('Boekbaar')->boolean(),
            ])
            ->defaultSort('sort')
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListAppointmentTypes::route('/'), 'create' => CreateAppointmentType::route('/create'), 'edit' => EditAppointmentType::route('/{record}/edit')];
    }
}
