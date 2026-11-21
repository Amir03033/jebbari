<?php

namespace App\Filament\Resources\Previews\Pages;

use App\Filament\Resources\Previews\PreviewResource;
use Filament\Resources\Pages\EditRecord;

/** Changes to the repository or branch take effect at the next deploy. */
class EditPreview extends EditRecord
{
    protected static string $resource = PreviewResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }
}
