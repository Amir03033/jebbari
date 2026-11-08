<?php

namespace App\Filament\Resources\Projects;

use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Support\PublishingUi;
use App\Filament\Support\Translated;
use App\Models\Project;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Inhoud';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'project';

    protected static ?string $pluralModelLabel = 'projecten';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            ...PublishingUi::callouts(),
            Section::make('Project')->schema([
                Translated::text('title', 'Titel', true),
                Translated::text('kind', 'Soort project (bijvoorbeeld boekingsapplicatie)', true),
                TextInput::make('year')->label('Jaar')->numeric()->minValue(2000)->maxValue(2100),
                TextInput::make('client_name')->label('Klant (alleen invullen als dat mag)')->maxLength(120),
                Translated::textarea('summary', 'Korte omschrijving (voor de projectkaart, één of twee zinnen)', false, 2),
                Translated::textarea('brief', 'De opdracht (één alinea)', false, 4),
                Translated::rich('built', 'Wat is gebouwd'),
                Translated::text('outcome', 'Resultaat (één eerlijke zin, alleen invullen als het klopt)'),
                TagsInput::make('stack')->label('Technologie'),
                TextInput::make('url')->label('Link naar het project (optioneel)')->url()->maxLength(255),
            ]),
            Section::make('Afbeeldingen')->schema([
                PublishingUi::mediaSelect('cover_media_id', 'Hoofdafbeelding'),
                Repeater::make('gallery')->label('Galerij')->reorderableWithButtons()->collapsible()->addActionLabel('Afbeelding toevoegen')
                    ->itemLabel(fn (array $state) => 'Afbeelding')
                    ->schema([
                        PublishingUi::mediaSelect('media_id', 'Afbeelding'),
                        Translated::text('caption', 'Bijschrift (optioneel)'),
                    ]),
            ]),
            Section::make('Uitlichten')->collapsed()->columns(['default' => 1, 'md' => 2])->schema([
                Toggle::make('is_featured')->label('Uitgelicht op de homepage'),
                TextInput::make('featured_sort')->label('Volgorde bij uitgelicht')->numeric()->default(0),
            ]),
            PublishingUi::pageSection(),
            PublishingUi::seoSection(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title.nl')->label('Titel')->weight('medium')
                    ->searchable(query: fn ($query, $search) => $query->where('title', 'like', "%{$search}%")),
                TextColumn::make('year')->label('Jaar')->sortable(),
                IconColumn::make('is_featured')->label('Uitgelicht')->boolean(),
                ...PublishingUi::tableColumns(),
                TextColumn::make('sort')->label('#')->sortable(),
            ])
            ->defaultSort('sort')
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }
}
