<?php

namespace App\Content\Blocks;

use App\Filament\Support\Translated;
use Filament\Forms\Components\Repeater;

/** A titled list of items with a Dutch/English text each: skills, principles, focus, tools. */
class ListBlock extends Block
{
    public function __construct(private string $type, private string $label, private bool $withDescription, private int $min = 1, private int $max = 12, private bool $single = true) {}

    public function singleton(): bool
    {
        return $this->single;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function required(): array
    {
        return ['label' => 'Label'];
    }

    public function repeaters(): array
    {
        return ['items' => $this->withDescription
            ? ['name' => 'Naam', 'description' => 'Beschrijving']
            : ['name' => 'Tekst']];
    }

    public function fields(): array
    {
        return [
            Translated::text('label', 'Sectielabel', true),
            Repeater::make('items')->label('Onderdelen')->minItems($this->min)->maxItems($this->max)->reorderableWithButtons()->collapsible()
                ->itemLabel(fn (array $state) => $state['name']['nl'] ?? 'Nieuw')
                ->schema([
                    Translated::text('name', $this->withDescription ? 'Naam' : 'Tekst', true),
                    ...($this->withDescription ? [Translated::textarea('description', 'Beschrijving', true)] : []),
                ]),
        ];
    }
}
