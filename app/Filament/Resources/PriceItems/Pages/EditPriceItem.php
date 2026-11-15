<?php

namespace App\Filament\Resources\PriceItems\Pages;

use App\Filament\Resources\PriceItems\PriceItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPriceItem extends EditRecord
{
    protected static string $resource = PriceItemResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
