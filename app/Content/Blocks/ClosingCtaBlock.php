<?php

namespace App\Content\Blocks;

use App\Filament\Support\Translated;

class ClosingCtaBlock extends Block
{
    public function type(): string
    {
        return 'closing_cta';
    }

    public function label(): string
    {
        return 'Afsluitende oproep';
    }

    public function required(): array
    {
        return ['headline' => 'Kop', 'booking_label' => 'Tekst afspraak-link'];
    }

    public function fields(): array
    {
        return [
            Translated::text('headline', 'Kop', true),
            Translated::text('booking_label', 'Tekst van de afspraak-link', true, 60),
        ];
    }
}
