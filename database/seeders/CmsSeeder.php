<?php

namespace Database\Seeders;

use App\Enums\PublishStatus;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Models\Project;
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
        $this->projects();
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
            'about' => ['Over mij', 'About'], 'contact' => ['Contact', 'Contact'], 'blog' => ['Blog', 'Blog'],
        ];

        foreach ($titles as $key => [$nl, $en]) {
            Page::query()->firstOrCreate(['key' => $key], [
                'kind' => 'system', 'title' => ['nl' => $nl, 'en' => $en], 'blocks' => $this->blocks($key), 'status' => PublishStatus::Draft,
            ]);
        }

        foreach (['privacy' => ['Privacy', 'Privacy'], 'terms' => ['Algemene voorwaarden', 'Terms and conditions']] as $key => [$nl, $en]) {
            Page::query()->firstOrCreate(['key' => $key], [
                'kind' => 'legal', 'title' => ['nl' => $nl, 'en' => $en], 'blocks' => $this->legalBlocks($key), 'status' => PublishStatus::Draft,
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
                    'cta_label' => ['nl' => 'Bekijk ons werk', 'en' => 'View our work'],
                    'cta_page' => 'work',
                    'cta2_label' => ['nl' => 'Neem contact op', 'en' => 'Get in touch'],
                    'cta2_page' => 'contact',
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
                // Personal introduction: written by the owner. Hidden until it exists.
                ['type' => 'about_teaser', 'data' => ['visible' => false, 'label' => ['nl' => 'Over mij', 'en' => 'About'], 'heading' => ['nl' => '', 'en' => ''], 'body' => ['nl' => '', 'en' => '']]],
                $this->block('spec_sheet', [
                    'label' => ['nl' => 'Gebouwd volgens een standaard', 'en' => 'Built to a standard'],
                    'title' => ['nl' => 'Gebouwd volgens een standaard', 'en' => 'Built to a standard'],
                ]),
                $this->block('closing_cta', [
                    'headline' => ['nl' => 'Iets te bouwen?', 'en' => 'Have something to build?'],
                    'booking_label' => ['nl' => 'Plan een gesprek', 'en' => 'Book a call'],
                ]),
            ],
            'services' => [
                $this->block('service_list', []),
                $this->block('packages_table', []),
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
        // Names as given by the owner. Summaries come from the brief where it has them;
        // the e-commerce line is a neutral draft for the owner to rewrite.
        $services = [
            [['Website-ontwikkeling', 'Website development'], ['Snelle, toegankelijke websites die je zelf kunt bijwerken.', 'Fast, accessible marketing sites you can edit yourself.']],
            [['Maatwerk webapplicaties', 'Custom web applications'], ['Maatwerk, veilig, snel. Boekingssystemen, dashboards, interne tools, portalen.', 'Custom, secure, fast. Booking systems, dashboards, internal tools, portals.']],
            [['E-commerce', 'E-commerce'], ['Webwinkels die passen bij hoe je verkoopt.', 'Online shops that fit how you sell.']],
            [['Onderhoud & hosting', 'Maintenance & hosting'], ['Updates, monitoring, back-ups, support. Software die af blijft.', 'Updates, monitoring, backups, support. Software that stays finished.']],
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

    /** Only what the brief states about ServerScope. Everything else is for the owner to add. */
    private function projects(): void
    {
        Project::query()->firstOrCreate(['slug_en' => 'serverscope'], [
            'title' => ['nl' => 'ServerScope', 'en' => 'ServerScope'],
            'kind' => ['nl' => 'Servermonitoring-dashboard', 'en' => 'Server monitoring dashboard'],
            'is_featured' => true,
            'sort' => 10,
            'status' => PublishStatus::Draft,
        ]);
    }

    private function navigation(): void
    {
        if (NavigationItem::query()->exists()) {
            return;
        }

        $items = [
            ['header', 'work', 'Werk', 'Work'], ['header', 'services', 'Diensten', 'Services'],
            ['header', 'about', 'Over mij', 'About'], ['header', 'blog', 'Blog', 'Blog'], ['header', 'contact', 'Contact', 'Contact'],
            ['footer', 'privacy', 'Privacy', 'Privacy'], ['footer', 'terms', 'Algemene voorwaarden', 'Terms and conditions'],
        ];

        foreach ($items as $i => [$location, $page, $nl, $en]) {
            NavigationItem::create(['location' => $location, 'page_key' => $page, 'label' => ['nl' => $nl, 'en' => $en], 'sort' => ($i + 1) * 10, 'is_visible' => true]);
        }
    }

    /**
     * Legal text drafts. They describe only what the website actually does and mark
     * every missing fact as [[ OWNER: ... ]]; the CMS refuses to publish a page that
     * still contains one. They are a starting point, not legal advice.
     */
    private function legalBlocks(string $key): array
    {
        if ($key === 'terms') {
            return [$this->block('rich_text', ['label' => ['nl' => '', 'en' => ''], 'body' => [
                'nl' => '<p>[[ OWNER: Algemene voorwaarden. Plaats hier de volledige voorwaarden die voor jouw dienstverlening gelden, bijvoorbeeld door een juridisch gecontroleerde tekst of een erkend modelvoorbeeld. ]]</p>',
                'en' => '<p>[[ OWNER: Terms and conditions. Place the full terms that apply to your services here, for example a legally reviewed text or a recognised model. ]]</p>',
            ]])];
        }

        return [$this->block('rich_text', ['label' => ['nl' => '', 'en' => ''], 'body' => [
            'nl' => '<h2>Wie is verantwoordelijk</h2><p>[[ OWNER: bedrijfsnaam, KvK-nummer en vestigingsadres ]] is verantwoordelijk voor de verwerking van persoonsgegevens via deze website.</p>'
                .'<h2>Welke gegevens we verwerken</h2><p>Als je het contactformulier gebruikt, slaan we je naam, e-mailadres, eventueel je bedrijfsnaam en budgetindicatie, en je bericht op. Daarnaast bewaren we een versleutelde aanduiding van je IP-adres om misbruik van het formulier tegen te gaan.</p>'
                .'<h2>Waarom</h2><p>We gebruiken deze gegevens om te reageren op je vraag en om een eventuele opdracht voor te bereiden.</p>'
                .'<h2>Cookies en meten</h2><p>Deze website gebruikt geen tracking- of advertentiecookies. [[ OWNER: bevestig of er geen analyse wordt gebruikt, of beschrijf de cookievrije analyse die je kiest ]]</p>'
                .'<h2>Bewaartermijn</h2><p>[[ OWNER: bewaartermijn van aanvragen ]]</p>'
                .'<h2>Delen met anderen</h2><p>[[ OWNER: noem hosting-, e-mail- en eventuele andere verwerkers ]]</p>'
                .'<h2>Je rechten</h2><p>Je kunt vragen om inzage, correctie of verwijdering van je gegevens en bezwaar maken tegen verwerking. Je kunt ook een klacht indienen bij de Autoriteit Persoonsgegevens.</p>'
                .'<h2>Contact</h2><p>[[ OWNER: e-mailadres voor privacyvragen ]]</p>',
            'en' => '<h2>Who is responsible</h2><p>[[ OWNER: company name, KvK number and registered address ]] is responsible for the processing of personal data through this website.</p>'
                .'<h2>What data we process</h2><p>If you use the contact form we store your name, email address, optionally your company name and budget range, and your message. We also keep a hashed indication of your IP address to prevent abuse of the form.</p>'
                .'<h2>Why</h2><p>We use this data to reply to your question and to prepare a possible project.</p>'
                .'<h2>Cookies and measuring</h2><p>This website uses no tracking or advertising cookies. [[ OWNER: confirm that no analytics is used, or describe the cookieless analytics you choose ]]</p>'
                .'<h2>Retention</h2><p>[[ OWNER: how long inquiries are kept ]]</p>'
                .'<h2>Sharing with others</h2><p>[[ OWNER: name hosting, email and any other processors ]]</p>'
                .'<h2>Your rights</h2><p>You can ask for access to, correction or deletion of your data and object to processing. You can also lodge a complaint with the Dutch Data Protection Authority (Autoriteit Persoonsgegevens).</p>'
                .'<h2>Contact</h2><p>[[ OWNER: email address for privacy questions ]]</p>',
        ]])];
    }
}
