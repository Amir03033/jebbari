<?php

namespace App\Content\Blocks;

use App\Models\MediaAsset;
use App\Models\Project;
use App\Models\Service;
use App\Models\Testimonial;

/** Choices offered in block forms (Dutch titles; records need a Dutch title to be listed). */
class BlockOptions
{
    public static function pages(): array
    {
        return ['contact' => 'Contact', 'work' => 'Werk', 'services' => 'Diensten', 'about' => 'Over mij', 'blog' => 'Blog'];
    }

    public static function services(): array
    {
        return Service::query()->orderBy('sort')->get()->mapWithKeys(fn ($s) => [$s->id => $s->t('title', 'nl') ?: "Dienst {$s->id}"])->all();
    }

    public static function projects(): array
    {
        return Project::query()->orderBy('sort')->get()->mapWithKeys(fn ($p) => [$p->id => $p->t('title', 'nl') ?: "Project {$p->id}"])->all();
    }

    public static function testimonials(): array
    {
        return Testimonial::query()->orderBy('sort')->get()->mapWithKeys(fn ($t) => [$t->id => $t->name.($t->company ? ", {$t->company}" : '')])->all();
    }

    public static function media(): array
    {
        return MediaAsset::query()->latest()->limit(200)->pluck('title', 'id')->all();
    }
}
