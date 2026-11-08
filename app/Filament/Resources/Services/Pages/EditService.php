<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Resources\Services\ServiceResource;
use App\Filament\Support\PublishableEditRecord;

class EditService extends PublishableEditRecord
{
    protected static string $resource = ServiceResource::class;
}
