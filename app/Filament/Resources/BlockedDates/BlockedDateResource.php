<?php

namespace App\Filament\Resources\BlockedDates;

use App\Filament\Resources\BlockedDates\Pages\CreateBlockedDate;
use App\Filament\Resources\BlockedDates\Pages\EditBlockedDate;
use App\Filament\Resources\BlockedDates\Pages\ListBlockedDates;
use App\Models\BlockedDate;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Days without appointments: holidays, days off. */
class BlockedDateResource extends Resource
{
    protected static ?string $model = BlockedDate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    protected static string|\UnitEnum|null $navigationGroup = 'Prijzen & afspraken';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'geblokkeerde dag';

    protected static ?string $pluralModelLabel = 'geblokkeerde dagen';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Dag')->columns(['default' => 1, 'md' => 2])->schema([
                DatePicker::make('date')->label('Datum')->required()->unique(ignoreRecord: true)->native(false)->displayFormat('d-m-Y'),
                TextInput::make('reason')->label('Reden (alleen voor jezelf)')->maxLength(255),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')->label('Datum')->date('l j F Y')->sortable(),
                TextColumn::make('reason')->label('Reden')->placeholder('—'),
            ])
            ->defaultSort('date')
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListBlockedDates::route('/'), 'create' => CreateBlockedDate::route('/create'), 'edit' => EditBlockedDate::route('/{record}/edit')];
    }
}
