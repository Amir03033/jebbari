<?php

namespace App\Content\Blocks;

use App\Filament\Support\Translated;

/** Section label, heading and a short text. Used at the top of several pages. */
class IntroBlock extends Block
{
    public function type(): string
    {
        return 'intro';
    }

    public function label(): string
    {
        return 'Introductie';
    }

    public function required(): array
    {
        return ['heading' => 'Kop'];
    }

    public function fields(): array
    {
        return [
            Translated::text('label', 'Sectielabel (optioneel)'),
            Translated::text('heading', 'Kop', true),
            Translated::textarea('body', 'Tekst (optioneel)', false, 4),
        ];
    }
}
