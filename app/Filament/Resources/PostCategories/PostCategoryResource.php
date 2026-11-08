<?php

namespace App\Filament\Resources\PostCategories;

use App\Filament\Resources\PostCategories\Pages\CreatePostCategory;
use App\Filament\Resources\PostCategories\Pages\EditPostCategory;
use App\Filament\Resources\PostCategories\Pages\ListPostCategories;
use App\Filament\Support\Translated;
use App\Models\PostCategory;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PostCategoryResource extends Resource
{
    protected static ?string $model = PostCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|\UnitEnum|null $navigationGroup = 'Inhoud';

    protected static ?int $navigationSort = 51;

    protected static ?string $modelLabel = 'categorie';

    protected static ?string $pluralModelLabel = 'categorieën';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Categorie')->columns(['default' => 1, 'md' => 2])->schema([
                Translated::text('name', 'Naam', true),
                TextInput::make('slug_nl')->label('Webadres (NL)')->helperText('Leeg laten om het automatisch te maken.')->alphaDash(),
                TextInput::make('slug_en')->label('Webadres (EN)')->helperText('Leeg laten om het automatisch te maken.')->alphaDash(),
                TextInput::make('sort')->label('Volgorde')->numeric()->default(0),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name.nl')->label('Naam (NL)')->weight('medium'),
                TextColumn::make('name.en')->label('Naam (EN)')->placeholder('Ontbreekt'),
                TextColumn::make('posts_count')->label('Artikelen')->counts('posts'),
            ])
            ->defaultSort('sort')
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPostCategories::route('/'),
            'create' => CreatePostCategory::route('/create'),
            'edit' => EditPostCategory::route('/{record}/edit'),
        ];
    }
}
