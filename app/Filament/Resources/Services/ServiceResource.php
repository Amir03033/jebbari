<?php

namespace App\Filament\Resources\Services;

use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Filament\Support\PublishingUi;
use App\Filament\Support\Translated;
use App\Models\Service;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|\UnitEnum|null $navigationGroup = 'Inhoud';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'dienst';

    protected static ?string $pluralModelLabel = 'diensten';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            ...PublishingUi::callouts(),
            Section::make('Inhoud')->schema([
                Translated::text('title', 'Titel', true),
                Translated::textarea('summary', 'Samenvatting (één zin)', true, 2),
                Translated::rich('description', 'Beschrijving'),
                Translated::textarea('benefits', 'Voordelen voor de klant (één punt per regel)', false, 4),
                Translated::textarea('included', 'Wat je oplevert (één punt per regel)', false, 4),
                Translated::textarea('not_included', 'Wat doen we niet (één punt per regel)', false, 3),
                Translated::text('timeline', 'Gebruikelijke doorlooptijd'),
                TagsInput::make('tags')->label('Labels (bijvoorbeeld Laravel)'),
                PublishingUi::mediaSelect('image_id', 'Afbeelding (optioneel)'),
            ]),
            Section::make('Pakketten en prijzen')->description('Prijzen verschijnen alleen als je ze invult. Leeg betekent "op aanvraag".')->collapsed()->schema([
                Repeater::make('packages')->label('Pakketten')->reorderableWithButtons()->collapsible()
                    ->itemLabel(fn (array $state) => $state['name']['nl'] ?? 'Nieuw pakket')->addActionLabel('Pakket toevoegen')
                    ->schema([
                        Translated::text('name', 'Naam van het pakket', true, 80),
                        Translated::textarea('scope', 'Wat zit erin (één punt per regel)', false, 4),
                        TextInput::make('price_from_eur')->label('Vanaf prijs (€, excl. btw)')->numeric()->minValue(0),
                        Translated::text('price_note', 'Toelichting bij de prijs (optioneel)'),
                        Toggle::make('visible')->label('Zichtbaar')->default(true),
                    ]),
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
                ...PublishingUi::tableColumns(),
                TextColumn::make('sort')->label('#')->sortable(),
            ])
            ->defaultSort('sort')
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'edit' => EditService::route('/{record}/edit'),
        ];
    }
}
