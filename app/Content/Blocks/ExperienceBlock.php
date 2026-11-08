<?php

namespace App\Content\Blocks;

use App\Filament\Support\Translated;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;

class ExperienceBlock extends Block
{
    public function type(): string
    {
        return 'experience';
    }

    public function label(): string
    {
        return 'Ervaring';
    }

    public function required(): array
    {
        return ['label' => 'Label'];
    }

    public function repeaters(): array
    {
        return ['items' => ['role' => 'Functie', 'description' => 'Beschrijving']];
    }

    public function fields(): array
    {
        return [
            Translated::text('label', 'Sectielabel', true),
            Repeater::make('items')->label('Onderdelen')->maxItems(20)->reorderableWithButtons()->collapsible()
                ->itemLabel(fn (array $state) => ($state['period'] ?? '').' '.($state['role']['nl'] ?? ''))
                ->schema([
                    TextInput::make('period')->label('Periode')->maxLength(40),
                    TextInput::make('organisation')->label('Organisatie')->maxLength(120),
                    Translated::text('role', 'Functie', true),
                    Translated::textarea('description', 'Beschrijving', true),
                ]),
        ];
    }
}
