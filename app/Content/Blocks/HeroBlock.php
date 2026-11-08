<?php

namespace App\Content\Blocks;

use App\Filament\Support\Translated;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class HeroBlock extends Block
{
    public function type(): string
    {
        return 'hero';
    }

    public function label(): string
    {
        return 'Hero (opening)';
    }

    public function required(): array
    {
        return ['label' => 'Label', 'headline' => 'Kop', 'description' => 'Beschrijving', 'cta_label' => 'Knoptekst'];
    }

    public function fields(): array
    {
        return [
            Translated::text('label', 'Label boven de kop', true),
            Translated::textarea('headline', 'Kop (één regel per rij)', true, 3),
            Toggle::make('end_with_stop')->label('Sluit de kop af met de punt (merkpunt)')->default(true),
            Translated::textarea('description', 'Beschrijving', true, 4),
            Translated::text('cta_label', 'Tekst van de knop', true, 60),
            Select::make('cta_page')->label('Knop verwijst naar')->options(BlockOptions::pages())->default('contact'),
            TextInput::make('cta_url')->label('Of eigen link (optioneel)')->url()->maxLength(255),
        ];
    }
}
