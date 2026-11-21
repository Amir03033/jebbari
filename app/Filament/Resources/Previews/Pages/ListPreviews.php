<?php

namespace App\Filament\Resources\Previews\Pages;

use App\Filament\Resources\Previews\PreviewResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPreviews extends ListRecords
{
    protected static string $resource = PreviewResource::class;

    protected function getHeaderActions(): array
    {
        return [PreviewResource::clearAction(), CreateAction::make()->label('Nieuwe preview')];
    }
}
