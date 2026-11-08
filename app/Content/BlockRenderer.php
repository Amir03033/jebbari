<?php

namespace App\Content;

use App\Content\Blocks\Blocks;
use App\Models\Page;
use Illuminate\Support\Collection;

/**
 * Decides which blocks of a page appear on the public site and how they are
 * numbered: hidden blocks, unknown types and blocks with nothing to show (for
 * example a services section while no service is published) are skipped, so
 * the section numbers always run 01, 02, 03 without gaps.
 */
class BlockRenderer
{
    private const NUMBERLESS = ['meeting_line', 'reply_promise', 'image'];

    /** @return Collection<int, array{type: string, data: array, number: string}> */
    public static function blocks(Page $page, int $start = 1): Collection
    {
        $n = $start - 1;

        return collect($page->blocks ?? [])
            ->filter(fn ($b) => Blocks::find($page->key, $b['type'] ?? '') && ($b['data']['visible'] ?? true) !== false)
            ->filter(fn ($b) => self::hasContent($b['type'], $b['data'] ?? []))
            ->map(function ($b) use (&$n) {
                // One-line texts and loose images carry no section label, so they take no number.
                if (! in_array($b['type'], self::NUMBERLESS, true)) {
                    $n++;
                }

                return ['type' => $b['type'], 'data' => $b['data'] ?? [], 'number' => str_pad((string) $n, 2, '0', STR_PAD_LEFT)];
            })->values();
    }

    public static function hasContent(string $type, array $data): bool
    {
        return match ($type) {
            'hero' => loc($data['headline'] ?? null) !== null,
            'services_overview', 'service_list' => Site::services(($data['mode'] ?? 'all') === 'selected' ? ($data['service_ids'] ?? []) : null)->isNotEmpty(),
            'work_selection' => Site::projects(($data['mode'] ?? 'featured') === 'featured', ($data['mode'] ?? 'featured') === 'selected' ? ($data['project_ids'] ?? []) : null)->isNotEmpty(),
            'packages_table' => PackagePricing::rows()->isNotEmpty(),
            'testimonials' => Site::testimonials(($data['mode'] ?? 'featured') === 'featured', ($data['mode'] ?? 'featured') === 'selected' ? ($data['testimonial_ids'] ?? []) : null)->isNotEmpty(),
            'spec_sheet' => ! empty(config('specs.values')),
            'process' => collect($data['steps'] ?? [])->contains(fn ($s) => loc($s['title'] ?? null) !== null),
            'about_teaser' => loc($data['heading'] ?? null) !== null && loc($data['body'] ?? null) !== null,
            'intro' => loc($data['heading'] ?? null) !== null,
            'closing_cta' => loc($data['headline'] ?? null) !== null,
            'rich_text', 'biography' => loc($data['body'] ?? null) !== null,
            'meeting_line', 'reply_promise' => loc($data['text'] ?? null) !== null,
            'image' => filled($data['media_id'] ?? null),
            'skills', 'principles', 'experience' => ! empty($data['items']),
            default => true,
        };
    }
}
