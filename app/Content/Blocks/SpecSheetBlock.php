<?php

namespace App\Content\Blocks;

use App\Filament\Support\Translated;
use Filament\Forms\Components\Placeholder;

class SpecSheetBlock extends Block
{
    public function type(): string
    {
        return 'spec_sheet';
    }

    public function label(): string
    {
        return 'Gebouwd volgens een standaard (specs)';
    }

    public function required(): array
    {
        return ['label' => 'Label', 'title' => 'Titel'];
    }

    public function fields(): array
    {
        return [
            Translated::text('label', 'Sectielabel', true),
            Translated::text('title', 'Titel', true),
            Translated::textarea('intro', 'Inleiding (optioneel)'),
            Placeholder::make('note')->label('Meetwaarden')->content('De waarden komen uit de metingen van deze site (config/specs.php) en zijn hier niet bewerkbaar.'),
        ];
    }
}
