<?php

namespace App\Filament\Resources\Previews\Pages;

use App\Filament\Resources\Previews\PreviewResource;
use App\Models\Preview;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

/** Creating a preview deploys it right away. */
class CreatePreview extends CreateRecord
{
    protected static string $resource = PreviewResource::class;

    protected static bool $canCreateAnother = false;

    protected function beforeCreate(): void
    {
        if (Preview::busy()) {
            Notification::make()->warning()->title('Er loopt al een build. Wacht tot die klaar is.')->send();
            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        $this->record->deploy();
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Aanmaken en deployen');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Preview aangemaakt; de build start binnen een minuut';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }
}
