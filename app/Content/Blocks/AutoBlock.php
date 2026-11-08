<?php

namespace App\Content\Blocks;

/** A section whose content is generated from other records (no fields to edit). */
class AutoBlock extends Block
{
    public function __construct(private string $type, private string $label) {}

    public function type(): string
    {
        return $this->type;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function fields(): array
    {
        return [];
    }
}
