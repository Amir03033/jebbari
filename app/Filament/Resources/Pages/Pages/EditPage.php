<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use App\Filament\Support\PublishableEditRecord;

class EditPage extends PublishableEditRecord
{
    protected static string $resource = PageResource::class;
}
