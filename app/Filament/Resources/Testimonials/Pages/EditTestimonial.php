<?php

namespace App\Filament\Resources\Testimonials\Pages;

use App\Filament\Resources\Testimonials\TestimonialResource;
use App\Filament\Support\PublishableEditRecord;

class EditTestimonial extends PublishableEditRecord
{
    protected static string $resource = TestimonialResource::class;
}
