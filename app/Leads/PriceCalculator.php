<?php

namespace App\Leads;

use App\Models\PriceItem;
use Illuminate\Support\Collection;

/**
 * The calculator's arithmetic, used live in the browser (same data) and here
 * on the server, where the saved estimate is always recomputed: the browser's
 * numbers are never trusted. A range, never a fixed price.
 */
class PriceCalculator
{
    /** @return Collection<string, Collection<int, PriceItem>> live items per group */
    public function items(): Collection
    {
        return PriceItem::query()->live()->get()->groupBy('group');
    }

    public function available(): bool
    {
        return $this->items()->has('project');
    }

    /**
     * Only known keys of the right group count; one project (required), one
     * size, one deadline, any extras.
     *
     * @param  array{project?: ?string, size?: ?string, deadline?: ?string, extras?: array}  $input
     * @return array{min: int, max: int, choices: array{project: ?string, size: ?string, deadline: ?string, extras: list<string>}, lines: list<array{key: string, group: string, min: int, max: int}>}|null
     */
    public function calculate(array $input): ?array
    {
        $items = PriceItem::query()->live()->get()->keyBy('key');
        $pick = fn (string $group, mixed $key) => is_string($key) && ($item = $items->get($key)) && $item->group === $group ? $item : null;

        $project = $pick('project', $input['project'] ?? null);

        if ($project === null) {
            return null;
        }

        $lines = array_values(array_filter([
            $project,
            $pick('size', $input['size'] ?? null),
            ...array_map(fn ($k) => $pick('extra', $k), array_unique(array_slice((array) ($input['extras'] ?? []), 0, 20))),
            $pick('deadline', $input['deadline'] ?? null),
        ]));

        return [
            'min' => array_sum(array_map(fn ($i) => $i->price_min, $lines)),
            'max' => array_sum(array_map(fn ($i) => $i->price_max, $lines)),
            'choices' => [
                'project' => $project->key,
                'size' => $pick('size', $input['size'] ?? null)?->key,
                'deadline' => $pick('deadline', $input['deadline'] ?? null)?->key,
                'extras' => array_values(array_map(fn ($i) => $i->key, array_filter($lines, fn ($i) => $i->group === 'extra'))),
            ],
            'lines' => array_map(fn ($i) => ['key' => $i->key, 'group' => $i->group, 'min' => $i->price_min, 'max' => $i->price_max], $lines),
        ];
    }

    public static function money(int $euros): string
    {
        return '€ '.number_format($euros, 0, ',', '.');
    }

    public static function range(?int $min, ?int $max): ?string
    {
        if ($min === null || $max === null) {
            return null;
        }

        return $min === $max ? self::money($min) : self::money($min).' – '.self::money($max);
    }
}
