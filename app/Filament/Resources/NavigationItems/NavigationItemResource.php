<?php

namespace App\Filament\Resources\NavigationItems;

use App\Filament\Resources\NavigationItems\Pages\CreateNavigationItem;
use App\Filament\Resources\NavigationItems\Pages\EditNavigationItem;
use App\Filament\Resources\NavigationItems\Pages\ListNavigationItems;
use App\Filament\Support\Translated;
use App\Models\NavigationItem;
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
use Filament\Tables\Table;

class NavigationItemResource extends Resource
{
    protected static ?string $model = NavigationItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static string|\UnitEnum|null $navigationGroup = 'Site';

    protected static ?int $navigationSort = 50;

    protected static ?string $modelLabel = 'menu-item';

    protected static ?string $pluralModelLabel = 'navigatie';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Menu-item')->columns(['default' => 1, 'md' => 2])->schema([
                Select::make('location')->label('Waar')->options(['header' => 'Kop (bovenaan)', 'footer' => 'Voet (onderaan)'])->required(),
                TextInput::make('sort')->label('Volgorde')->numeric()->default(0),
                Select::make('page_key')->label('Verwijst naar pagina')->placeholder('Eigen link gebruiken')
                    ->options(['home' => 'Home', 'work' => 'Werk', 'services' => 'Diensten', 'about' => 'Over', 'contact' => 'Contact', 'blog' => 'Blog', 'privacy' => 'Privacy', 'terms' => 'Algemene voorwaarden']),
                TextInput::make('url')->label('Of eigen link')->url()->maxLength(255),
                Toggle::make('is_visible')->label('Zichtbaar')->default(true),
            ]),
            Section::make('Tekst')->schema([Translated::text('label', 'Label', true, 40)]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label.nl')->label('Label (NL)')->weight('medium'),
                TextColumn::make('label.en')->label('Label (EN)')->placeholder('Ontbreekt'),
                TextColumn::make('location')->label('Waar')->badge()->formatStateUsing(fn ($state) => $state === 'header' ? 'Kop' : 'Voet'),
                IconColumn::make('is_visible')->label('Zichtbaar')->boolean(),
                TextColumn::make('sort')->label('#')->sortable(),
            ])
            ->defaultSort('sort')
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNavigationItems::route('/'),
            'create' => CreateNavigationItem::route('/create'),
            'edit' => EditNavigationItem::route('/{record}/edit'),
        ];
    }
}
