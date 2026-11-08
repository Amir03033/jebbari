<?php

namespace App\Content\Blocks;

use App\Filament\Support\Translated;
use Filament\Forms\Components\Select;

class TestimonialsBlock extends Block
{
    public function type(): string
    {
        return 'testimonials';
    }

    public function label(): string
    {
        return 'Referenties (citaten)';
    }

    public function required(): array
    {
        return ['label' => 'Label'];
    }

    public function fields(): array
    {
        return [
            Translated::text('label', 'Sectielabel', true),
            Select::make('mode')->label('Welke referenties')->options(['featured' => 'Uitgelichte referenties', 'selected' => 'Alleen mijn selectie'])->default('featured')->live(),
            Select::make('testimonial_ids')->label('Selectie')->multiple()->options(fn () => BlockOptions::testimonials())->visible(fn ($get) => $get('mode') === 'selected'),
        ];
    }
}
