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
            Translated::text('cta_label', 'Hoofdknop: tekst', true, 60),
            Select::make('cta_page')->label('Hoofdknop verwijst naar')->options(BlockOptions::pages())->default('work'),
            TextInput::make('cta_url')->label('Hoofdknop: eigen link (optioneel)')->url()->maxLength(255),
            Translated::text('cta2_label', 'Tweede knop: tekst (optioneel)', false, 60),
            Select::make('cta2_page')->label('Tweede knop verwijst naar')->options(BlockOptions::pages())->default('contact'),
            TextInput::make('cta2_url')->label('Tweede knop: eigen link (optioneel)')->url()->maxLength(255),
        ];
    }
}
