<?php

namespace App\Filament\Resources\Previews\Pages;

use App\Filament\Resources\Previews\PreviewResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPreview extends ViewRecord
{
    protected static string $resource = PreviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PreviewResource::deployAction(),
            Action::make('open')->label('Klantpagina')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                ->url(fn () => $this->getRecord()->reviewUrl(), true),
            EditAction::make()->color('gray'),
            PreviewResource::clearAction(),
            DeleteAction::make()->visible(fn () => ! $this->getRecord()->status->isBusy()),
        ];
    }
}
