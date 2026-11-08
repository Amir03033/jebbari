<?php

namespace App\Filament\Resources\Testimonials;

use App\Filament\Resources\Testimonials\Pages\CreateTestimonial;
use App\Filament\Resources\Testimonials\Pages\EditTestimonial;
use App\Filament\Resources\Testimonials\Pages\ListTestimonials;
use App\Filament\Support\PublishingUi;
use App\Filament\Support\Translated;
use App\Models\Testimonial;
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

class TestimonialResource extends Resource
{
    protected static ?string $model = Testimonial::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static string|\UnitEnum|null $navigationGroup = 'Inhoud';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'referentie';

    protected static ?string $pluralModelLabel = 'referenties';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            ...PublishingUi::callouts(),
            Section::make('Referentie')->schema([
                TextInput::make('name')->label('Naam')->required()->maxLength(120),
                TextInput::make('company')->label('Bedrijf')->maxLength(120),
                TextInput::make('role')->label('Functie')->maxLength(120),
                Translated::textarea('quote', 'Citaat (echt en letterlijk)', true, 4),
                Toggle::make('permission_confirmed')->label('Ik heb toestemming om dit citaat te publiceren')
                    ->helperText('Zonder toestemming kan een referentie niet gepubliceerd worden.'),
                Toggle::make('is_featured')->label('Uitgelicht'),
                TextInput::make('sort')->label('Volgorde')->numeric()->default(0),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Naam')->weight('medium')->searchable(),
                TextColumn::make('company')->label('Bedrijf')->searchable(),
                IconColumn::make('permission_confirmed')->label('Toestemming')->boolean(),
                ...PublishingUi::tableColumns(),
            ])
            ->defaultSort('sort')
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTestimonials::route('/'),
            'create' => CreateTestimonial::route('/create'),
            'edit' => EditTestimonial::route('/{record}/edit'),
        ];
    }
}
