<?php

namespace App\Filament\Pages\Settings;

use App\Filament\Support\Translated;
use BackedEnum;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

class HeaderFooterSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedViewColumns;

    protected static ?string $navigationLabel = 'Kop en voet';

    protected static ?string $title = 'Kop en voet';

    protected static ?int $navigationSort = 40;

    protected static function group(): string
    {
        return 'header';
    }

    protected function components(): array
    {
        return [
            Section::make('Statusregel in de kop')->schema([
                Toggle::make('taking_projects')->label('Ik neem nieuwe projecten aan')->default(true),
                Translated::text('status_open', 'Tekst als je projecten aanneemt', false, 60),
                Translated::text('status_closed', 'Tekst als je volgeboekt bent', false, 60),
            ]),
            Section::make('Voet')->schema([
                Translated::textarea('footer_text', 'Extra tekst in de voet (optioneel)', false, 2),
            ]),
        ];
    }
}
