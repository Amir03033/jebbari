<?php

/**
 * The text of a translatable value ({"nl": ..., "en": ...}) in one language.
 * Returns null when it is missing: the public site never shows another
 * language's text in its place.
 */
function loc(mixed $value, ?string $locale = null): ?string
{
    if (! is_array($value)) {
        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    $text = $value[$locale ?? app()->getLocale()] ?? null;

    return is_string($text) && trim(strip_tags($text)) !== '' ? $text : null;
}
