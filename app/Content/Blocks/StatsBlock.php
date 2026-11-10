<?php

namespace App\Content\Blocks;

use App\Filament\Support\Translated;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;

/**
 * A few real numbers (for example years of experience, projects delivered). The
 * numbers count up when they come into view. Nothing is shown until the owner
 * fills it in: no placeholder figures.
 */
class StatsBlock extends Block
{
    public function type(): string
    {
        return 'stats';
    }

    public function label(): string
    {
        return 'Cijfers (alleen echte cijfers)';
    }

    public function required(): array
    {
        return ['label' => 'Label'];
    }

    public function repeaters(): array
    {
        return ['items' => ['label' => 'Omschrijving']];
    }

    public function fields(): array
    {
        return [
            Translated::text('label', 'Sectielabel', true),
            Repeater::make('items')->label('Cijfers')->minItems(1)->maxItems(4)->reorderableWithButtons()->collapsible()
                ->itemLabel(fn (array $state) => ($state['value'] ?? '').' '.($state['label']['nl'] ?? ''))
                ->schema([
                    TextInput::make('value')->label('Getal')->numeric()->minValue(0)->maxValue(999999)->required(),
                    TextInput::make('suffix')->label('Teken erachter (optioneel, bijv. + of %)')->maxLength(4),
                    Translated::text('label', 'Omschrijving', true),
                ]),
        ];
    }
}
