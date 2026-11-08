<?php

namespace App\Content\Blocks;

use App\Models\Concerns\Translatable;
use Filament\Forms\Components\Builder\Block as BuilderBlock;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;

/**
 * One typed section of a page. A block owns its editing form, its list of
 * required translations and (in the page phases) its Blade view. Editors fill
 * content only: there are no style or layout options.
 */
abstract class Block
{
    abstract public function type(): string;

    abstract public function label(): string;

    /** @return array<int, Component> */
    abstract public function fields(): array;

    /** Sections that may appear only once on a page. */
    public function singleton(): bool
    {
        return true;
    }

    /** @return array<string, string> translatable field => label needed before publishing */
    public function required(): array
    {
        return [];
    }

    /** @return array<string, array<string, string>> repeater key => [translatable field => label] */
    public function repeaters(): array
    {
        return [];
    }

    /** Non-translation problems, e.g. a missing image. @return list<string> */
    public function issues(array $data): array
    {
        return [];
    }

    /** @return list<int|string|null> */
    public function mediaIds(array $data): array
    {
        return [];
    }

    public function builderBlock(): BuilderBlock
    {
        return BuilderBlock::make($this->type())
            ->label($this->label())
            ->schema([
                Toggle::make('visible')->label('Zichtbaar op de website')->default(true),
                ...$this->fields(),
            ]);
    }

    /** @return list<string> */
    public function gaps(array $data, string $locale): array
    {
        $out = [];

        foreach ($this->required() as $field => $label) {
            if (! Translatable::filled(data_get($data, "$field.$locale"))) {
                $out[] = $this->label().': '.$label;
            }
        }

        foreach ($this->repeaters() as $key => $fields) {
            foreach (($data[$key] ?? []) as $i => $item) {
                foreach ($fields as $field => $label) {
                    if (! Translatable::filled(data_get($item, "$field.$locale"))) {
                        $out[] = $this->label().': '.$label.' ('.($i + 1).')';
                    }
                }
            }
        }

        return $out;
    }
}
