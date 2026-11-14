<?php

namespace App\Content\Blocks;

use App\Filament\Support\Translated;

/** One rich text section: biography, legal text. */
class RichTextBlock extends Block
{
    public function __construct(private string $type = 'rich_text', private string $label = 'Tekst', private bool $single = true) {}

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
        return ['body' => 'Tekst'];
    }

    public function fields(): array
    {
        return [
            Translated::text('label', 'Sectielabel (optioneel)'),
            Translated::rich('body', 'Tekst', true),
        ];
    }
}
