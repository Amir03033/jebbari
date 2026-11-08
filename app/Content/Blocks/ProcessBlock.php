<?php

namespace App\Content\Blocks;

use App\Filament\Support\Translated;
use Filament\Forms\Components\Repeater;

class ProcessBlock extends Block
{
    public function type(): string
    {
        return 'process';
    }

    public function label(): string
    {
        return 'Werkwijze (stappen)';
    }

    public function required(): array
    {
        return ['label' => 'Label'];
    }

    public function repeaters(): array
    {
        return ['steps' => ['title' => 'Titel van stap', 'description' => 'Beschrijving van stap']];
    }

    public function fields(): array
    {
        return [
            Translated::text('label', 'Sectielabel', true),
            Repeater::make('steps')->label('Stappen')->minItems(3)->maxItems(6)->reorderableWithButtons()->collapsible()
                ->itemLabel(fn (array $state) => $state['title']['nl'] ?? 'Stap')
                ->schema([
                    Translated::text('title', 'Titel', true, 60),
                    Translated::textarea('description', 'Beschrijving', true),
                ]),
        ];
    }
}
