<?php

namespace App\Content\Blocks;

use App\Filament\Support\Translated;

/** A single line of text: meeting line, reply promise. */
class LineBlock extends Block
{
    public function __construct(private string $type, private string $label) {}

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
        return ['text' => 'Tekst'];
    }

    public function fields(): array
    {
        return [Translated::text('text', 'Tekst', true)];
    }
}
