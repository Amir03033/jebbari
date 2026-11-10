<?php

namespace App\Filament\Pages\Settings;

use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

class SocialSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static ?string $navigationLabel = 'Sociale links';

    protected static ?string $title = 'Sociale links';

    protected static ?int $navigationSort = 30;

    protected static function group(): string
    {
        return 'social';
    }

    protected function components(): array
    {
        return [
            Section::make('GitHub')->description('Met een gebruikersnaam kan het onderdeel "GitHub-projecten" je openbare repositories tonen. De server haalt ze op; bezoekers maken geen verbinding met GitHub.')->schema([
                TextInput::make('github_username')->label('GitHub-gebruikersnaam (optioneel)')->regex('/^[A-Za-z0-9-]{1,39}$/')->maxLength(39),
            ]),
            Section::make('Links')->description('Worden als tekstlinks in de voet getoond.')->schema([
                Repeater::make('links')->label('Links')->reorderableWithButtons()->addActionLabel('Link toevoegen')
                    ->itemLabel(fn (array $state) => $state['label'] ?? 'Nieuwe link')->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextInput::make('label')->label('Naam (bijvoorbeeld LinkedIn)')->required()->maxLength(40),
                        TextInput::make('url')->label('Adres')->url()->required()->maxLength(255),
                    ]),
            ]),
        ];
    }
}
