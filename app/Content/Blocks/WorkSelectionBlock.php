<?php

namespace App\Content\Blocks;

use App\Filament\Support\Translated;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class WorkSelectionBlock extends Block
{
    public function type(): string
    {
        return 'work_selection';
    }

    public function label(): string
    {
        return 'Werk (selectie)';
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
            Select::make('mode')->label('Welke projecten')->options(['featured' => 'Uitgelichte projecten', 'selected' => 'Alleen mijn selectie'])->default('featured')->live(),
            Select::make('project_ids')->label('Selectie')->multiple()->options(fn () => BlockOptions::projects())->visible(fn ($get) => $get('mode') === 'selected'),
            TextInput::make('limit')->label('Maximaal aantal')->numeric()->minValue(1)->maxValue(4)->default(4),
        ];
    }
}
