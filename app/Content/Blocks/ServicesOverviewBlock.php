<?php

namespace App\Content\Blocks;

use App\Filament\Support\Translated;
use Filament\Forms\Components\Select;

class ServicesOverviewBlock extends Block
{
    public function type(): string
    {
        return 'services_overview';
    }

    public function label(): string
    {
        return 'Diensten (overzicht)';
    }

    public function required(): array
    {
        return ['label' => 'Label'];
    }

    public function fields(): array
    {
        return [
            Translated::text('label', 'Sectielabel', true),
            Translated::textarea('intro', 'Inleiding (optioneel)'),
            Select::make('mode')->label('Welke diensten')->options(['all' => 'Alle gepubliceerde diensten', 'selected' => 'Alleen mijn selectie'])->default('all')->live(),
            Select::make('service_ids')->label('Selectie')->multiple()->options(fn () => BlockOptions::services())->visible(fn ($get) => $get('mode') === 'selected'),
        ];
    }
}
