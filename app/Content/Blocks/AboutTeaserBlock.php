<?php

namespace App\Content\Blocks;

use App\Filament\Support\Translated;
use Filament\Forms\Components\Select;

/** Short personal introduction on the homepage, linking to the About page. */
class AboutTeaserBlock extends Block
{
    public function type(): string
    {
        return 'about_teaser';
    }

    public function label(): string
    {
        return 'Over mij (kort)';
    }

    public function required(): array
    {
        return ['label' => 'Label', 'heading' => 'Kop', 'body' => 'Tekst'];
    }

    public function fields(): array
    {
        return [
            Translated::text('label', 'Sectielabel', true),
            Translated::text('heading', 'Kop', true),
            Translated::textarea('body', 'Persoonlijke introductie (alleen wat klopt)', true, 5),
            Select::make('media_id')->label('Foto (optioneel)')->options(fn () => BlockOptions::media())->searchable()->placeholder('Geen foto'),
        ];
    }

    public function mediaIds(array $data): array
    {
        return [$data['media_id'] ?? null];
    }
}
