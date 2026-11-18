<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Fills the gaps found on the live site (read in full on 18 November 2026),
 * using only facts that are already on jebbari.nl: the About text, the stack
 * page, the project pages, the changelog and the ServerScope article.
 *
 * Everything goes in as unpublished changes through saveFromForm() (the draft
 * rule), so the live site does not change until the owner publishes. Each step
 * only touches a field that is still empty or still in the old voice, so running
 * it twice, or after the owner has edited something, changes nothing.
 *
 * Run with `php artisan content:fill`.
 */
class SiteFillSeeder extends Seeder
{
    /** @var list<string> what happened, for the command's report */
    public static array $report = [];

    /** @var list<string> what only the owner can add */
    public static array $todo = [];

    public function run(): void
    {
        self::$report = [];
        self::$todo = [];

        $this->about();
        $this->uses();
        $this->homeSeo();
        $this->services();
        $this->projects();
    }

    // ------------------------------------------------------------------ pages

    /** The CV takes its skills from the About page; without a skills block it had none. */
    private function about(): void
    {
        $page = Page::query()->where('key', 'about')->first();

        if (! $page) {
            return;
        }

        $blocks = $page->withDraft()->blocks ?? [];

        if (in_array('skills', array_column($blocks, 'type'), true)) {
            self::$report[] = 'Over mij: heeft al vaardigheden, ongewijzigd';

            return;
        }

        $skills = $this->block('skills', [
            'label' => $this->t('Vaardigheden', 'Skills'),
            'items' => [
                ['name' => $this->t('Webapplicaties en SaaS', 'Web applications and SaaS'), 'description' => $this->t('Maatwerk webapplicaties, van boekingssystemen en dashboards tot offline-first webapps en eigen SaaS-producten.', 'Custom web applications, from booking systems and dashboards to offline-first web apps and SaaS products of my own.')],
                ['name' => $this->t('Backend', 'Backend'), 'description' => $this->t('Laravel (PHP), Filament en Livewire, REST API\'s en webhooks, MySQL en MariaDB.', 'Laravel (PHP), Filament and Livewire, REST APIs and webhooks, MySQL and MariaDB.')],
                ['name' => $this->t('Frontend', 'Frontend'), 'description' => $this->t('Vue 3, TypeScript, JavaScript, Blade en Tailwind CSS. Snel, rustig en minimaal.', 'Vue 3, TypeScript, JavaScript, Blade and Tailwind CSS. Fast, calm and minimal.')],
                ['name' => $this->t('Servers en beveiliging', 'Servers and security'), 'description' => $this->t('Linux-VPS, Nginx, Plesk en Git-deployment, met bijzondere aandacht voor server- en applicatiebeveiliging.', 'Linux VPS, Nginx, Plesk and Git deployment, with particular attention to server and application security.')],
                ['name' => $this->t('Monitoring', 'Monitoring'), 'description' => $this->t('Beschikbaarheid, prestaties en incidenten in één overzicht, met mijn eigen platform ServerScope.', 'Availability, performance and incidents in one view, with my own platform ServerScope.')],
            ],
        ]);

        // Before the way of working, if the page has one; else before the last section.
        $at = array_search('process', array_column($blocks, 'type'), true);
        $at = $at === false ? max(0, count($blocks) - 1) : $at;
        array_splice($blocks, $at, 0, [$skills]);

        $page->saveFromForm(['blocks' => $blocks]);
        self::$report[] = 'Over mij: vaardigheden toegevoegd (vult ook het cv)'.$this->state($page);
    }

    /** The stack page listed the site and the basics; the tools behind the products were missing. */
    private function uses(): void
    {
        $page = Page::query()->where('key', 'uses')->first();

        if (! $page) {
            return;
        }

        $blocks = $page->withDraft()->blocks ?? [];
        $labels = collect($blocks)->map(fn ($b) => $b['data']['label']['nl'] ?? null)->filter()->all();

        if (in_array('Eigen producten', $labels, true)) {
            self::$report[] = 'Stack: ongewijzigd';

            return;
        }

        $products = $this->block('gear', [
            'label' => $this->t('Eigen producten', 'My own products'),
            'items' => [
                ['name' => $this->t('Filament en Livewire', 'Filament and Livewire'), 'description' => $this->t('Beheerpanelen, zoals het eigen beheer van amirs.life.', 'Admin panels, such as the custom admin of amirs.life.')],
                ['name' => $this->t('Python', 'Python'), 'description' => $this->t('De agent van ServerScope, die op servers systeemgegevens verzamelt.', 'The ServerScope agent, which collects system data on servers.')],
                ['name' => $this->t('React, Vite en Chart.js', 'React, Vite and Chart.js'), 'description' => $this->t('Het dashboard en de prestatiegrafieken van ServerScope.', 'The ServerScope dashboard and its performance charts.')],
                ['name' => $this->t('MariaDB en MySQL', 'MariaDB and MySQL'), 'description' => $this->t('De databases onder BarberCuts, ServerScope en amirs.life.', 'The databases under BarberCuts, ServerScope and amirs.life.')],
                ['name' => $this->t('Nginx en Plesk', 'Nginx and Plesk'), 'description' => $this->t('Webserver en serverbeheer, met deployment via Git.', 'Web server and server management, with deployment through Git.')],
                ['name' => $this->t('AI-agents', 'AI agents'), 'description' => $this->t('Ondersteuning bij het samenstellen van content voor Irooni.', 'Support in putting together content for Irooni.')],
            ],
        ]);

        // After the development tools: before the servers, which close the page.
        $last = count($blocks) - 1;
        $at = $last >= 0 && ($blocks[$last]['type'] ?? null) === 'gear' ? $last : count($blocks);
        array_splice($blocks, $at, 0, [$products]);

        $page->saveFromForm(['blocks' => $blocks]);
        self::$report[] = 'Stack: blok "Eigen producten" toegevoegd'.$this->state($page);
    }

    /** The homepage description still spoke of "wij" and a "digital studio"; the rest of the site says "ik". */
    private function homeSeo(): void
    {
        $page = Page::query()->where('key', 'home')->first();

        if (! $page) {
            return;
        }

        $seo = $page->withDraft()->seo ?? [];
        $current = (string) ($seo['description']['nl'] ?? '');

        if ($current !== '' && ! preg_match('/\b(wij|we)\b|digital studio/i', $current)) {
            self::$report[] = 'Home: SEO-omschrijving ongewijzigd';

            return;
        }

        $seo['description'] = $this->t(
            'Amir Jebbari ontwerpt en bouwt websites, webapplicaties en eigen softwareproducten met Laravel. Vanuit Zwolle en Amsterdam, voor klanten in heel Nederland.',
            'Amir Jebbari designs and builds websites, web applications and software products with Laravel. Based in Zwolle and Amsterdam, working with clients across the Netherlands.',
        );

        $page->saveFromForm(['seo' => $seo]);
        self::$report[] = 'Home: SEO-omschrijving in de ik-vorm'.$this->state($page);
    }

    // --------------------------------------------------------------- services

    /** Several services still spoke as "wij" / "we"; the site speaks in the first person. */
    private function services(): void
    {
        foreach ($this->serviceTexts() as $slug => $text) {
            $service = Service::query()->where('slug_en', $slug)->first();

            if (! $service) {
                continue;
            }

            $current = $service->withDraft();
            $old = strip_tags(($current->summary['nl'] ?? '').' '.($current->description['nl'] ?? ''));

            if (! preg_match('/\b(wij|we|ons|onze)\b/i', $old)) {
                self::$report[] = 'Dienst '.$slug.': al in de ik-vorm, ongewijzigd';

                continue;
            }

            $service->saveFromForm(array_filter([
                'summary' => $text['summary'] ?? null,
                'description' => $text['description'],
            ]));
            self::$report[] = 'Dienst '.$slug.': tekst in de ik-vorm'.$this->state($service);
        }
    }

    /** @return array<string, array{summary?: array, description: array}> */
    private function serviceTexts(): array
    {
        $p = fn (string ...$lines) => '<p>'.implode('</p><p>', $lines).'</p>';

        return [
            'website-development' => ['description' => $this->t(
                $p(
                    'Een website is vaak het eerste contactmoment tussen jouw bedrijf en een nieuwe klant. Daarom bouw ik websites die niet alleen goed ogen, maar ook technisch betrouwbaar, gebruiksvriendelijk en gericht op resultaat zijn.',
                    'Ik begin met het begrijpen van jouw bedrijf, doelgroep en doelen. Vanuit die basis ontwerp en bouw ik een website die past bij je merk en wat je ermee wilt bereiken.',
                    'Van een overzichtelijke bedrijfswebsite of landingspagina tot een uitgebreide site met meerdere pagina\'s, koppelingen en dynamische onderdelen. Elke website bouw ik met aandacht voor detail, met de nadruk op snelheid, responsive design, toegankelijkheid en vindbaarheid.',
                    'Het resultaat is een professionele online aanwezigheid die met je bedrijf kan meegroeien.',
                ),
                $p(
                    'A website is often the first point of contact between your business and a new customer. That is why I build websites that do not just look good, but are also technically reliable, easy to use and built with a clear goal.',
                    'I start by understanding your business, your audience and your goals. From there I design and build a website that fits your brand and what you want it to achieve.',
                    'From a clear business website or landing page to an extensive site with multiple pages, integrations and dynamic parts. I build every website with attention to detail and a focus on speed, responsive design, accessibility and search visibility.',
                    'The result is a professional online presence that can grow with your business.',
                ),
            )],
            'custom-web-applications' => ['description' => $this->t(
                $p(
                    'Niet elk bedrijfsproces past in standaardsoftware. Soms is er een oplossing nodig die precies aansluit op je eigen werkwijze, gebruikers en doelen.',
                    'Ik bouw webapplicaties op maat. Ik vertaal ideeën, processen en technische eisen naar betrouwbare software met een duidelijke structuur en een gebruikerservaring die vanzelf spreekt.',
                    'Denk aan klantportalen, dashboards, reserveringssystemen, interne beheertools, SaaS-platformen en koppelingen met andere systemen.',
                    'Ik let op de zichtbare interface én op wat eronder zit: gebruikersrollen, inloggen, de database, beveiliging, koppelingen en schaalbaarheid.',
                    'Van de eerste technische analyse tot bouwen, testen en livegang werk ik aan software die vandaag werkt en klaar is voor wat er later bij komt.',
                ),
                $p(
                    'Not every business process fits standard software. Sometimes you need a solution that matches your own way of working, your users and your goals.',
                    'I build custom web applications. I turn ideas, processes and technical requirements into reliable software with a clear structure and a user experience that speaks for itself.',
                    'Think of customer portals, dashboards, booking systems, internal admin tools, SaaS platforms and connections with other systems.',
                    'I look after the visible interface and what lies beneath it: user roles, sign-in, the database, security, integrations and scalability.',
                    'From the first technical analysis to building, testing and going live, I work on software that works today and is ready for what comes next.',
                ),
            )],
            'e-commerce' => ['description' => $this->t(
                $p(
                    'Een goede webshop vraagt om meer dan een mooi ontwerp. De hele winkelervaring moet logisch, betrouwbaar en eenvoudig zijn.',
                    'Ik bouw webshops die passen bij je producten, je bedrijfsmodel en je commerciële doelen, met een verzorgde productpresentatie, overzichtelijke categorieën, een soepel bestelproces en een technisch betrouwbare basis.',
                    'Waar nodig breid ik een webshop uit met productbeheer, voorraad, klantaccounts, kortingscodes, betaalkoppelingen, orderbeheer en koppelingen met andere systemen.',
                    'Ik besteed extra aandacht aan gebruiksgemak, snelheid op mobiel, beveiliging en een bestelproces dat altijd werkt.',
                    'Of je nu een nieuwe webshop start of een bestaande winkel wilt vernieuwen: ik bouw een oplossing die past bij jouw onderneming.',
                ),
                $p(
                    'A good online store needs more than an attractive design. The whole shopping experience has to be logical, reliable and simple.',
                    'I build online stores that fit your products, your business model and your commercial goals, with well-presented products, clear categories, a smooth checkout and a technically reliable foundation.',
                    'Where needed I extend a store with product management, stock, customer accounts, discount codes, payment integrations, order management and connections with other systems.',
                    'I pay particular attention to usability, mobile speed, security and a checkout that always works.',
                    'Whether you are starting a new store or modernising an existing one: I build a solution that fits your business.',
                ),
            )],
            'api-development-integrations' => [
                'summary' => $this->t(
                    'Ik verbind systemen, applicaties en externe diensten met betrouwbare API\'s en koppelingen die bedrijfsprocessen efficiënter maken.',
                    'I connect systems, applications and external services with reliable APIs and integrations that make business processes more efficient.',
                ),
                'description' => $this->t(
                    $p(
                        'Bedrijven werken met verschillende systemen die niet vanzelf met elkaar praten. Met API\'s en koppelingen op maat zorg ik dat ze samenwerken.',
                        'Ik bouw API-koppelingen, integreer externe diensten en ontwikkel oplossingen die gegevens veilig en gestructureerd uitwisselen. Denk aan koppelingen met betaalsystemen, CRM-pakketten, boekhoudsoftware en andere applicaties.',
                        'Of het nu gaat om het automatiseren van terugkerend werk of het uitbreiden van een bestaande applicatie: ik bouw technische oplossingen die aansluiten op wat er al is en op wat er nog komt.',
                    ),
                    $p(
                        'Businesses rely on several systems that do not talk to each other by themselves. With custom APIs and integrations I make them work together.',
                        'I build API connections, integrate external services and develop solutions that exchange data securely and consistently. Think of connections with payment providers, CRM software, accounting software and other applications.',
                        'Whether it is automating repetitive work or extending an existing application: I build technical solutions that fit what is already there and what is still to come.',
                    ),
                ),
            ],
            'maintenance-hosting' => ['description' => $this->t(
                $p(
                    'Een website of webapplicatie heeft ook na de lancering aandacht nodig. Software verandert, beveiligingsupdates verschijnen en afhankelijkheden moeten regelmatig worden bijgewerkt.',
                    'Met onderhoud en hosting zorg ik dat je digitale omgeving up-to-date blijft en betrouwbaar draait.',
                    'Ik help met serverbeheer, software-updates, beveiligingscontroles, back-ups, monitoring en technische optimalisatie, en met hostingconfiguratie, domeinkoppelingen, SSL-certificaten en deployment.',
                    'Afhankelijk van wat we afspreken voer ik ook periodieke controles uit en zoek ik technische problemen uit zodra ze zich voordoen.',
                    'Zo hoef jij je minder met de techniek bezig te houden en kun je je richten op je onderneming.',
                ),
                $p(
                    'A website or web application needs attention after launch too. Software changes, security updates are released and dependencies need regular updates.',
                    'With maintenance and hosting I keep your digital environment up to date and running reliably.',
                    'I help with server administration, software updates, security checks, backups, monitoring and technical optimisation, and with hosting configuration, domains, SSL certificates and deployment.',
                    'Depending on what we agree, I also run periodic checks and look into technical problems as soon as they arise.',
                    'That way you spend less time on the technology and more on your business.',
                ),
            )],
            'uiux-digital-product-design' => [
                'summary' => $this->t(
                    'Ik ontwerp heldere interfaces en doordachte digitale ervaringen die functionaliteit, gebruiksgemak en een sterke visuele identiteit combineren.',
                    'I design clear interfaces and thoughtful digital experiences that combine functionality, usability and a strong visual identity.',
                ),
                'description' => $this->t(
                    $p(
                        'Een goed digitaal product begint bij een goede gebruikerservaring. Ik ontwerp interfaces die niet alleen mooi zijn, maar ook logisch, toegankelijk en prettig in gebruik.',
                        'Van de eerste wireframes tot complete schermen en klikbare prototypes: ik vertaal ideeën en bedrijfsdoelen naar concrete ontwerpen.',
                        'In elk ontwerp staat de gebruiker centraal. Ik let op navigatie, visuele hiërarchie, consistentie en responsive design. Het resultaat is een doordachte basis voor websites, webapplicaties en softwareproducten.',
                    ),
                    $p(
                        'A good digital product starts with a good user experience. I design interfaces that are not only attractive but also logical, accessible and pleasant to use.',
                        'From the first wireframes to complete screens and clickable prototypes: I turn ideas and business goals into concrete designs.',
                        'Every design is built around the user. I pay attention to navigation, visual hierarchy, consistency and responsive design. The result is a well-considered foundation for websites, web applications and software products.',
                    ),
                ),
            ],
            'seo-performance-optimization' => [
                'summary' => $this->t(
                    'Ik optimaliseer websites voor betere vindbaarheid, snellere laadtijden en een technisch sterke basis voor online groei.',
                    'I optimise websites for better search visibility, faster loading times and a technically solid foundation for online growth.',
                ),
                'description' => $this->t(
                    $p(
                        'Een website moet niet alleen goed werken, maar ook goed vindbaar en technisch in orde zijn. Ik verbeter websites op het gebied van technische SEO, snelheid en structuur.',
                        'Ik analyseer de technische basis en zoek verbeterpunten in laadsnelheid, indexeerbaarheid, metadata, interne links en prestaties op mobiel.',
                        'Daarnaast kijk ik naar de instellingen voor zoekmachines, gestructureerde gegevens en de opbouw van de site. Zo ontstaat een solide basis om beter gevonden te worden en bezoekers een snellere ervaring te geven.',
                    ),
                    $p(
                        'A website should not only work well, it should also be easy to find and technically sound. I improve websites in technical SEO, speed and structure.',
                        'I analyse the technical foundation and look for improvements in loading speed, indexability, metadata, internal links and mobile performance.',
                        'I also look at search engine settings, structured data and the structure of the site. That creates a solid foundation to be found more easily and to give visitors a faster experience.',
                    ),
                ),
            ],
        ];
    }

    // --------------------------------------------------------------- projects

    private function projects(): void
    {
        $this->project('BarberCuts', [
            'outcome' => $this->t(
                'Versie 1.0 staat sinds november 2026 live op barbercuts.nl, met afspraken, klanten, diensten en beschikbaarheid in één platform.',
                'Version 1.0 has been live on barbercuts.nl since November 2026, with appointments, clients, services and availability in one platform.',
            ),
        ]);

        $this->project('Irooni', []);

        $this->project('ServerScope', [
            // From the article "Van idee naar product: de ontwikkeling van ServerScope". No dates: only real ones go here.
            'timeline' => [
                ['date' => '', 'title' => $this->t('Eerste basis: serverstatistieken', 'First foundation: server statistics'), 'text' => $this->t('Een Python-agent verzamelt CPU-, geheugen-, schijf- en netwerkgegevens en stuurt ze naar de Laravel-backend.', 'A Python agent collects CPU, memory, disk and network data and sends it to the Laravel back end.')],
                ['date' => '', 'title' => $this->t('Domeinmonitoring en incidentbeheer', 'Domain monitoring and incident management'), 'text' => $this->t('HTTP-beschikbaarheid, SSL-certificaten, DNS-records en domeinvervaldatums, met overzichtelijke registratie van incidenten.', 'HTTP availability, SSL certificates, DNS records and domain expiry dates, with clear incident records.')],
                ['date' => '', 'title' => $this->t('Meldingen', 'Alerts'), 'text' => $this->t('Configureerbare alerts en Discord-notificaties, zodat je op tijd weet dat er iets misgaat.', 'Configurable alerts and Discord notifications, so you know in time when something goes wrong.')],
                ['date' => '', 'title' => $this->t('Integraties', 'Integrations'), 'text' => $this->t('Een REST API, signed webhooks en publieke statuspagina\'s maken het platform uitbreidbaar.', 'A REST API, signed webhooks and public status pages make the platform extensible.')],
            ],
        ]);

        $this->project('ReviewsPilot', [
            'outcome' => $this->t(
                'Een uitgewerkt productconcept met dashboardontwerp en functionele opzet, als basis voor de verdere ontwikkeling.',
                'A worked-out product concept with a dashboard design and functional outline, as the basis for further development.',
            ),
        ]);

        $this->project('amirs.life', []);
    }

    /** Fill only what is empty, and switch on live measuring for projects with a link. */
    private function project(string $name, array $fill): void
    {
        $project = Project::query()->get()->first(fn (Project $p) => str_contains((string) ($p->title['nl'] ?? ''), $name));

        if (! $project) {
            return;
        }

        $current = $project->withDraft();
        $changes = [];

        foreach ($fill as $field => $value) {
            if ($this->isEmpty($current->{$field})) {
                $changes[$field] = $value;
            }
        }

        if ($current->url && ! $current->monitor) {
            $changes['monitor'] = true;
        }

        foreach (['url' => 'link naar de live site', 'stack' => 'technologie', 'cover_media_id' => 'hoofdafbeelding'] as $field => $label) {
            if ($this->isEmpty($current->{$field})) {
                self::$todo[] = "$name: $label ontbreekt";
            }
        }

        if (empty($current->gallery)) {
            self::$todo[] = "$name: geen schermafbeeldingen in de galerij (sectie Interface)";
        }

        if (! $changes) {
            self::$report[] = "Project $name: ongewijzigd";

            return;
        }

        $project->saveFromForm($changes);
        self::$report[] = "Project $name: ".implode(', ', array_map(fn ($f) => match ($f) {
            'outcome' => 'resultaat',
            'timeline' => 'tijdlijn',
            'monitor' => 'live meten aan',
            default => $f,
        }, array_keys($changes))).$this->state($project);
    }

    // ---------------------------------------------------------------- helpers

    private function isEmpty(mixed $value): bool
    {
        if (is_array($value)) {
            return collect($value)->filter(fn ($v) => is_array($v) ? ! $this->isEmpty($v) : filled($v))->isEmpty();
        }

        return blank($value);
    }

    private function state($record): string
    {
        return $record->isPublished() ? ' (niet-gepubliceerde wijziging)' : ' (concept)';
    }

    private function block(string $type, array $data): array
    {
        return ['type' => $type, 'data' => ['visible' => true, ...$data]];
    }

    private function t(string $nl, string $en): array
    {
        return ['nl' => $nl, 'en' => $en];
    }
}
