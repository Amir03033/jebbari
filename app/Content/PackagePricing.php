<?php

namespace App\Content;

use App\Support\Locale;
use Illuminate\Support\Collection;

/** Packages and "from" prices, collected from the live services. Prices are only what the owner entered. */
class PackagePricing
{
    /** @return Collection<int, array{service: string, url: string, name: string, scope: list<string>, price: ?int, note: ?string}> */
    public static function rows(?Collection $services = null): Collection
    {
        $services ??= Site::services();

        return $services->flatMap(fn ($service) => collect($service->packages ?? [])
            ->filter(fn ($p) => ($p['visible'] ?? true) !== false && loc($p['name'] ?? null))
            ->map(fn ($p) => [
                'service' => loc($service->title),
                'url' => Locale::route('services.show', ['slug' => $service->slugFor(app()->getLocale())]),
                'name' => loc($p['name']),
                'scope' => self::lines(loc($p['scope'] ?? null)),
                'price' => isset($p['price_from_eur']) && is_numeric($p['price_from_eur']) ? (int) $p['price_from_eur'] : null,
                'note' => loc($p['price_note'] ?? null),
            ]))->values();
    }

    /** @return list<string> */
    public static function lines(?string $text): array
    {
        return collect(preg_split('/\R/', (string) $text))->map(fn ($l) => trim($l))->filter()->values()->all();
    }

    public static function format(int $euros): string
    {
        return '€ '.number_format($euros, 0, ',', '.');
    }
}
