<?php

namespace App\Filament\Resources\NavigationItems\Pages;

use App\Filament\Resources\NavigationItems\NavigationItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNavigationItem extends CreateRecord
{
    protected static string $resource = NavigationItemResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
