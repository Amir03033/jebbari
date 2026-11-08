<?php

namespace App\Filament\Pages\Settings;

use App\Filament\Support\PublishingUi;
use App\Filament\Support\Translated;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

class SeoSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?string $navigationLabel = 'SEO-standaarden';

    protected static ?string $title = 'SEO-standaarden';

    protected static ?int $navigationSort = 60;

    protected static function group(): string
    {
        return 'seo';
    }

    protected function components(): array
    {
        return [
            Section::make('Standaardwaarden')->description('Worden gebruikt als een pagina of artikel zelf geen SEO-gegevens heeft.')->schema([
                TextInput::make('site_name')->label('Sitenaam')->default('Jebbari')->maxLength(60),
                TextInput::make('title_template')->label('Titelsjabloon')->default('{title} · Jebbari')->helperText('{title} wordt de titel van de pagina.')->maxLength(80),
                Translated::textarea('default_description', 'Standaard beschrijving (max. 160 tekens)', false, 3),
                PublishingUi::mediaSelect('default_og_media_id', 'Standaard afbeelding bij delen'),
            ]),
        ];
    }
}
