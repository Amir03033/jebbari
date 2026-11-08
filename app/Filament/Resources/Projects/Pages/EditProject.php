<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Support\PublishableEditRecord;

class EditProject extends PublishableEditRecord
{
    protected static string $resource = ProjectResource::class;
}
