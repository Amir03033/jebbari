<?php

namespace App\Models\Concerns;

use App\Models\MediaAsset;

/** For content that points at media assets. Publishing requires alt text in both languages. */
trait ReferencesMedia
{
    /** @return list<int> */
    abstract public function mediaIds(): array;

    /** @return list<string> */
    public function mediaIssues(): array
    {
        $ids = array_values(array_unique(array_filter($this->mediaIds())));

        if ($ids === []) {
            return [];
        }

        $issues = [];

        foreach (MediaAsset::query()->whereIn('id', $ids)->get() as $asset) {
            foreach ($asset->missingAlt() as $locale) {
                $issues[] = "Alt-tekst ({$locale}) ontbreekt voor afbeelding \"{$asset->title}\"";
            }
        }

        return $issues;
    }
}
