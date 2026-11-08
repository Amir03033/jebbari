<?php

namespace App\Filament\Resources\MediaAssets;

use App\Filament\Resources\MediaAssets\Pages\CreateMediaAsset;
use App\Filament\Resources\MediaAssets\Pages\EditMediaAsset;
use App\Filament\Resources\MediaAssets\Pages\ListMediaAssets;
use App\Filament\Support\Translated;
use App\Models\MediaAsset;
use App\Support\MediaSizes;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MediaAssetResource extends Resource
{
    protected static ?string $model = MediaAsset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|\UnitEnum|null $navigationGroup = 'Inhoud';

    protected static ?int $navigationSort = 60;

    protected static ?string $modelLabel = 'afbeelding';

    protected static ?string $pluralModelLabel = 'media';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Afbeelding')->schema([
                TextInput::make('title')->label('Naam (alleen intern)')->required()->maxLength(120),
                SpatieMediaLibraryFileUpload::make('file')->label('Bestand')->collection('file')->conversion('thumb')
                    ->image()->acceptedFileTypes(MediaSizes::mimes())->maxSize(MediaSizes::MAX_KB)
                    ->rules(['dimensions:min_width='.MediaSizes::MIN_WIDTH.',max_width='.MediaSizes::MAX_WIDTH])
                    ->helperText('JPEG, PNG'.(in_array('image/avif', MediaSizes::mimes(), true) ? ', WebP of AVIF' : ' of WebP').', maximaal 10 MB. Locatiegegevens (EXIF) worden verwijderd.')
                    ->required(),
            ]),
            Section::make('Alt-tekst')->description('Beschrijf wat er te zien is, voor schermlezers en zoekmachines. Verplicht voor publiceren, tenzij de afbeelding puur decoratief is.')->schema([
                Toggle::make('is_decorative')->label('Alleen decoratief (geen alt-tekst nodig)'),
                Translated::text('alt', 'Alt-tekst', false, 200),
            ]),
            Section::make('Gebruikt in')->collapsed()->visible(fn (?Model $record) => $record?->exists ?? false)->schema([
                Placeholder::make('usages')->hiddenLabel()
                    ->content(fn (?Model $record) => $record?->usages() ? implode(' · ', $record->usages()) : 'Nergens. Deze afbeelding kan veilig worden verwijderd.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('file')->label('')->collection('file')->conversion('thumb')->square()->size(56),
                TextColumn::make('title')->label('Naam')->weight('medium')->searchable(),
                IconColumn::make('alt_ok')->label('Alt-tekst')->boolean()->state(fn (MediaAsset $r) => $r->missingAlt() === []),
                TextColumn::make('created_at')->label('Toegevoegd')->dateTime('d-m-Y')->timezone('Europe/Amsterdam')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMediaAssets::route('/'),
            'create' => CreateMediaAsset::route('/create'),
            'edit' => EditMediaAsset::route('/{record}/edit'),
        ];
    }
}
