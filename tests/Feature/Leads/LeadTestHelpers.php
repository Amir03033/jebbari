<?php

namespace Tests\Feature\Leads;

use App\Models\AppointmentType;
use App\Models\AvailabilityRule;
use App\Models\PriceItem;
use Illuminate\Support\Facades\Crypt;

trait LeadTestHelpers
{
    protected function prices(): void
    {
        foreach ([
            ['project', 'website', 'Website', 2000, 4000],
            ['project', 'webapp', 'Webapplicatie', 8000, 20000],
            ['size', 'small', 'Klein', 0, 500],
            ['extra', 'cms', 'CMS', 800, 1500],
            ['extra', 'multilingual', 'Meertalig', 500, 1000],
            ['deadline', 'rush', 'Met spoed', 1000, 2000],
        ] as $i => [$group, $key, $name, $min, $max]) {
            PriceItem::create(['group' => $group, 'key' => $key, 'name' => ['nl' => $name, 'en' => $name], 'price_min' => $min, 'price_max' => $max, 'is_active' => true, 'sort' => $i]);
        }

        // Hidden and unpriced lines never count.
        PriceItem::create(['group' => 'extra', 'key' => 'hidden', 'name' => ['nl' => 'Verborgen', 'en' => 'Hidden'], 'price_min' => 99999, 'price_max' => 99999, 'is_active' => false]);
    }

    protected function onlineType(int $minutes = 30): AppointmentType
    {
        return AppointmentType::create(['name' => ['nl' => 'Online kennismaking', 'en' => 'Online introduction'], 'location' => ['nl' => 'Online', 'en' => 'Online'], 'duration_minutes' => $minutes, 'is_online' => true, 'is_active' => true]);
    }

    /** Monday to Friday, 09:00 to 12:00 Dutch time. */
    protected function weekdayMornings(?AppointmentType $type = null): void
    {
        foreach (range(1, 5) as $day) {
            AvailabilityRule::create(['weekday' => $day, 'starts_at' => '09:00', 'ends_at' => '12:00', 'appointment_type_id' => $type?->id]);
        }
    }

    protected function stamp(): string
    {
        return Crypt::encryptString((string) (time() - 30));
    }
}
