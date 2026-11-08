<?php

namespace App\Filament\Support;

use Closure;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

/**
 * A field that exists once per language. Dutch and English sit side by side on
 * wide screens and stacked on phones, and are edited independently. Values are
 * stored as {"nl": ..., "en": ...} in one JSON column.
 */
class Translated
{
    public const LOCALES = ['nl' => 'NL', 'en' => 'EN'];

    public static function text(string $name, string $label, bool $required = false, ?int $max = 255): Grid
    {
        return self::pair($name, $label, $required, fn (string $path) => TextInput::make($path)->maxLength($max));
    }

    public static function textarea(string $name, string $label, bool $required = false, int $rows = 3): Grid
    {
        return self::pair($name, $label, $required, fn (string $path) => Textarea::make($path)->rows($rows));
    }

    public static function rich(string $name, string $label, bool $required = false): Grid
    {
        return self::pair($name, $label, $required, fn (string $path) => RichEditor::make($path)
            ->toolbarButtons([['bold', 'italic', 'link'], ['h2', 'h3'], ['bulletList', 'orderedList', 'blockquote'], ['undo', 'redo']]));
    }

    private static function pair(string $name, string $label, bool $required, Closure $make): Grid
    {
        $fields = [];

        foreach (self::LOCALES as $locale => $tag) {
            $fields[] = $make("{$name}.{$locale}")
                ->label("{$label} ({$tag})".($required ? ' *' : ''))
                ->extraAttributes(['lang' => $locale])
                ->hint(fn ($state) => $required && ! self::hasContent($state) ? 'Ontbreekt' : null)
                ->hintColor('warning');
        }

        return Grid::make(['default' => 1, 'md' => 2])->schema($fields)->columnSpanFull();
    }

    private static function hasContent(mixed $state): bool
    {
        return is_string($state) && trim(html_entity_decode(strip_tags($state))) !== '';
    }
}
