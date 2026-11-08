<?php

namespace App\Filament\Pages\Settings;

use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

class ContactSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Contact en afspraak';

    protected static ?string $title = 'Contact en afspraak';

    protected static ?int $navigationSort = 20;

    protected static function group(): string
    {
        return 'contact';
    }

    protected function components(): array
    {
        return [
            Section::make('Contact')->columns(['default' => 1, 'md' => 2])->schema([
                TextInput::make('email')->label('E-mailadres')->email()->maxLength(160),
                TextInput::make('booking_url')->label('Link om een gesprek te plannen')->url()->maxLength(255),
                TextInput::make('notify_email')->label('Meldingen van nieuwe aanvragen naar')->email()->maxLength(160)
                    ->helperText('Leeg laten om het e-mailadres hierboven te gebruiken.'),
            ]),
        ];
    }
}
