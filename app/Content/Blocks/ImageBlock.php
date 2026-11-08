<?php

namespace App\Content\Blocks;

use App\Filament\Support\Translated;
use Filament\Forms\Components\Select;

class ImageBlock extends Block
{
    public function type(): string
    {
        return 'image';
    }

    public function label(): string
    {
        return 'Afbeelding';
    }

    public function singleton(): bool
    {
        return false;
    }

    public function fields(): array
    {
        return [
            Select::make('media_id')->label('Afbeelding')->options(fn () => BlockOptions::media())->searchable()->placeholder('Kies een afbeelding uit de mediabibliotheek'),
            Translated::text('caption', 'Bijschrift (optioneel)'),
        ];
    }

    public function issues(array $data): array
    {
        return blank($data['media_id'] ?? null) ? ['Afbeelding: er is geen afbeelding gekozen'] : [];
    }

    public function mediaIds(array $data): array
    {
        return [$data['media_id'] ?? null];
    }
}
