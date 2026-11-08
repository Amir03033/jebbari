<?php

namespace Database\Seeders;

use App\Enums\PublishStatus;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Models\Service;
use App\Support\Settings;
use Illuminate\Database\Seeder;

/**
 * Starting content taken from the design brief only. Dutch text for sentences
 * the brief gives only in English is a draft translation for the owner to
 * review. Everything is seeded as a DRAFT: nothing is live until published.
 */
class CmsSeeder extends Seeder
{
    public function run(): void
    {
        $this->pages();
        $this->services();
        $this->navigation();

        Settings::save('header', [
            'taking_projects' => true,
            'status_open' => ['nl' => 'Neemt projecten aan', 'en' => 'Taking on projects'],
            'status_closed' => ['nl' => 'Volgeboekt', 'en' => 'Fully booked'],
        ]);
    }

    private function pages(): void
    {
        $titles = [
            'home' => ['Home', 'Home'], 'work' => ['Werk', 'Work'], 'services' => ['Diensten', 'Services'],
            'about' => ['Over ons', 'About'], 'contact' => ['Contact', 'Contact'], 'blog' => ['Blog', 'Blog'],
        ];

        foreach ($titles as $key => [$nl, $en]) {
            Page::query()->firstOrCreate(['key' => $key], [
                'kind' => 'system', 'title' => ['nl' => $nl, 'en' => $en], 'blocks' => $this->blocks($key), 'status' => PublishStatus::Draft,
            ]);
        }

        foreach (['privacy' => ['Privacy', 'Privacy'], 'terms' => ['Algemene voorwaarden', 'Terms and conditions']] as $key => [$nl, $en]) {
            Page::query()->firstOrCreate(['key' => $key], [
                'kind' => 'legal', 'title' => ['nl' => $nl, 'en' => $en], 'blocks' => [], 'status' => PublishStatus::Draft,
            ]);
        }
    }

    private function block(string $type, array $data): array
    {
        return ['type' => $type, 'data' => ['visible' => true, ...$data]];
    }

    private function blocks(string $key): array
    {
        return match ($key) {
            'home' => [
                $this->block('hero', [
                    'label' => ['nl' => 'Softwarestudio, Zwolle & Amsterdam', 'en' => 'Software studio, Zwolle & Amsterdam'],
                    'headline' => ['nl' => "Software\ndie klopt", 'en' => "Software, done\nproperly"],
                    'end_with_stop' => true,
                    'description' => [
                        'nl' => 'Wij ontwerpen en bouwen webapplicaties en websites voor bedrijven die er om geven hoe hun software voelt, presteert en standhoudt.',
                        'en' => 'We design and build web applications and websites for businesses that care how their software feels, performs and lasts.',
                    ],
                    'cta_label' => ['nl' => 'Start een project', 'en' => 'Start a project'],
                    'cta_page' => 'contact',
                ]),
                $this->block('services_overview', ['label' => ['nl' => 'Diensten', 'en' => 'Services'], 'mode' => 'all']),
                $this->block('work_selection', ['label' => ['nl' => 'Werk', 'en' => 'Work'], 'mode' => 'featured', 'limit' => 4]),
                $this->block('process', [
                    'label' => ['nl' => 'Werkwijze', 'en' => 'Process'],
                    'steps' => [
                        ['title' => ['nl' => 'Praten', 'en' => 'Talk'], 'description' => ['nl' => 'We luisteren, stellen de lastige vragen en leggen scope en prijs schriftelijk vast.', 'en' => 'We listen, ask the awkward questions, and agree scope and price in writing.']],
                        ['title' => ['nl' => 'Ontwerpen', 'en' => 'Design'], 'description' => ['nl' => 'Wireframes en een klikbaar ontwerp dat jij goedkeurt voordat er code wordt geschreven.', 'en' => 'Wireframes and a clickable design you approve before any code.']],
                        ['title' => ['nl' => 'Bouwen', 'en' => 'Build'], 'description' => ['nl' => 'Wekelijks werkende versies die je kunt openen en gebruiken. Geen verrassingen.', 'en' => 'Weekly working builds you can open and use. No surprises.']],
                        ['title' => ['nl' => 'Lanceren', 'en' => 'Launch'], 'description' => ['nl' => 'We zetten live, houden toezicht en blijven bereikbaar.', 'en' => 'We ship, monitor, and stay on call.']],
                    ],
                ]),
                $this->block('spec_sheet', [
                    'label' => ['nl' => 'Gebouwd volgens een standaard', 'en' => 'Built to a standard'],
                    'title' => ['nl' => 'Gebouwd volgens een standaard', 'en' => 'Built to a standard'],
                ]),
                $this->block('closing_cta', [
                    'headline' => ['nl' => 'Iets te bouwen?', 'en' => 'Have something to build?'],
                    'booking_label' => ['nl' => 'Plan een gesprek', 'en' => 'Book a call'],
                ]),
            ],
            'contact' => [
                $this->block('meeting_line', ['text' => ['nl' => 'Afspreken in Zwolle, Amsterdam of online.', 'en' => 'Meet in Zwolle, Amsterdam or online.']]),
                $this->block('reply_promise', ['text' => ['nl' => 'Wij reageren binnen één werkdag.', 'en' => 'We reply within one working day.']]),
            ],
            default => [],
        };
    }

    private function services(): void
    {
        $services = [
            [['Webapplicaties', 'Web applications'], ['Maatwerk, veilig, snel. Boekingssystemen, dashboards, interne tools, portalen.', 'Custom, secure, fast. Booking systems, dashboards, internal tools, portals.']],
            [['Websites', 'Websites'], ['Snelle, toegankelijke websites die je zelf kunt bijwerken.', 'Fast, accessible marketing sites you can edit yourself.']],
            [['Onderhoud & hosting', 'Care & hosting'], ['Updates, monitoring, back-ups, support. Software die af blijft.', 'Updates, monitoring, backups, support. Software that stays finished.']],
        ];

        foreach ($services as $i => [[$titleNl, $titleEn], [$summaryNl, $summaryEn]]) {
            Service::query()->firstOrCreate(['slug_en' => str($titleEn)->slug()->toString()], [
                'title' => ['nl' => $titleNl, 'en' => $titleEn],
                'summary' => ['nl' => $summaryNl, 'en' => $summaryEn],
                'sort' => ($i + 1) * 10,
                'status' => PublishStatus::Draft,
            ]);
        }
    }

    private function navigation(): void
    {
        if (NavigationItem::query()->exists()) {
            return;
        }

        $items = [
            ['header', 'work', 'Werk', 'Work'], ['header', 'services', 'Diensten', 'Services'],
            ['header', 'about', 'Over ons', 'About'], ['header', 'contact', 'Contact', 'Contact'],
            ['footer', 'privacy', 'Privacy', 'Privacy'], ['footer', 'terms', 'Algemene voorwaarden', 'Terms and conditions'],
        ];

        foreach ($items as $i => [$location, $page, $nl, $en]) {
            NavigationItem::create(['location' => $location, 'page_key' => $page, 'label' => ['nl' => $nl, 'en' => $en], 'sort' => ($i + 1) * 10, 'is_visible' => true]);
        }
    }
}
