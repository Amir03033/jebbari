<?php

namespace App\Content\Blocks;

use App\Filament\Support\Translated;
use Filament\Forms\Components\Repeater;

/** Questions and answers, shown as an accordion and offered to search engines as FAQ data. */
class FaqBlock extends Block
{
    public function type(): string
    {
        return 'faq';
    }

    public function label(): string
    {
        return 'Veelgestelde vragen';
    }

    public function required(): array
    {
        return ['label' => 'Label'];
    }

    public function repeaters(): array
    {
        return ['items' => ['question' => 'Vraag', 'answer' => 'Antwoord']];
    }

    public function fields(): array
    {
        return [
            Translated::text('label', 'Sectielabel', true),
            Translated::text('heading', 'Kop (optioneel)'),
            Repeater::make('items')->label('Vragen')->minItems(1)->maxItems(12)->reorderableWithButtons()->collapsible()
                ->itemLabel(fn (array $state) => $state['question']['nl'] ?? 'Nieuwe vraag')
                ->schema([
                    Translated::text('question', 'Vraag', true),
                    Translated::textarea('answer', 'Antwoord', true, 3),
                ]),
        ];
    }
}
