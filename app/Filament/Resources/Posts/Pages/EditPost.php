<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostResource;
use App\Filament\Support\PublishableEditRecord;
use Filament\Actions\RestoreAction;

class EditPost extends PublishableEditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [...parent::getHeaderActions(), RestoreAction::make()];
    }
}
