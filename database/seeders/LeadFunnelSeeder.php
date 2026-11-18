<?php

namespace Database\Seeders;

use App\Models\AppointmentType;
use App\Models\PriceItem;
use Illuminate\Database\Seeder;

/**
 * The structure of the price calculator and the meeting types from the
 * LeadPlan. No prices are invented: every price line starts hidden and
 * without amounts until the owner fills them in (Admin > Prijzen & afspraken).
 * Only adds what is missing, so it is safe on every deploy and never
 * overwrites the owner's changes.
 */
class LeadFunnelSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['project', 'website', 'Website', 'Website'],
            ['project', 'webapp', 'Webapplicatie', 'Web application'],
            ['project', 'booking', 'Boekingssysteem', 'Booking system'],
            ['project', 'webshop', 'Webshop', 'Online shop'],
            ['size', 'small', 'Klein (tot 5 pagina\'s of functies)', 'Small (up to 5 pages or features)'],
            ['size', 'medium', 'Middel (6 tot 15)', 'Medium (6 to 15)'],
            ['size', 'large', 'Groot (meer dan 15)', 'Large (more than 15)'],
            ['extra', 'cms', 'Zelf teksten aanpassen (CMS)', 'Edit content yourself (CMS)'],
            ['extra', 'multilingual', 'Meertalig', 'Multilingual'],
            ['extra', 'seo', 'SEO-basis', 'SEO foundations'],
            ['extra', 'maintenance', 'Onderhoud (eerste jaar)', 'Maintenance (first year)'],
            ['deadline', 'standard', 'Normale planning', 'Normal schedule'],
            ['deadline', 'rush', 'Met spoed', 'Rush'],
        ];

        foreach ($items as $sort => [$group, $key, $nl, $en]) {
            PriceItem::query()->firstOrCreate(['key' => $key], [
                'group' => $group,
                'name' => ['nl' => $nl, 'en' => $en],
                'is_active' => false,
                'sort' => $sort * 10,
            ]);
        }

        if (! AppointmentType::query()->exists()) {
            AppointmentType::create([
                'name' => ['nl' => 'Kennismaking online', 'en' => 'Introduction online'],
                'location' => ['nl' => 'Online (videobellen)', 'en' => 'Online (video call)'],
                'duration_minutes' => 30,
                'is_online' => true,
                'is_active' => true,
                'sort' => 10,
            ]);

            // In person: off until the owner confirms the places (only one address is published).
            foreach ([['Zwolle', 20], ['Amsterdam', 30]] as [$city, $sort]) {
                AppointmentType::create([
                    'name' => ['nl' => "Kennismaking in {$city}", 'en' => "Introduction in {$city}"],
                    'location' => ['nl' => $city, 'en' => $city],
                    'duration_minutes' => 45,
                    'is_online' => false,
                    'is_active' => false,
                    'sort' => $sort,
                ]);
            }
        }
    }
}
