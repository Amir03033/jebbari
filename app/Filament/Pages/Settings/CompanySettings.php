<?php

namespace App\Filament\Pages\Settings;

use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

class CompanySettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Bedrijfsgegevens';

    protected static ?string $title = 'Bedrijfsgegevens';

    protected static ?int $navigationSort = 10;

    protected static function group(): string
    {
        return 'company';
    }

    protected function components(): array
    {
        return [
            Section::make('Bedrijf')->description('Verschijnt in de voet van de website en in de zoekmachinegegevens.')->columns(['default' => 1, 'md' => 2])->schema([
                TextInput::make('legal_name')->label('Bedrijfsnaam (statutair)')->maxLength(160),
                TextInput::make('kvk')->label('KvK-nummer')->maxLength(20),
            ]),
        ];
    }
}
