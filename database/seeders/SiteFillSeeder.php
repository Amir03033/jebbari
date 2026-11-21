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
        $this->privacy();
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

    /**
     * The privacy statement covered only the contact form. It now also covers
     * the website check and watch, the calculator, requests, appointments,
     * proposals, the newsletter and the statistics, with the retention the code
     * really applies. The owner's facts (Onholland B.V., KvK, address, the 12
     * months for contact) are kept as they are on the live page.
     */
    private function privacy(): void
    {
        $page = Page::query()->where('key', 'privacy')->first();

        if (! $page) {
            return;
        }

        $current = json_encode($page->withDraft()->blocks ?? []);

        if (str_contains($current, 'Website-check en website-watch')) {
            self::$report[] = 'Privacy: al bijgewerkt, ongewijzigd';

            return;
        }

        $body = $this->t(
            '<h2>Wie is verantwoordelijk?</h2>'
            .'<p>Onholland B.V., handelend onder de naam Jebbari, is verantwoordelijk voor de verwerking van persoonsgegevens via deze website.</p>'
            .'<ul><li>Bedrijfsnaam: Onholland B.V.</li><li>Handelsnaam: Jebbari</li><li>KvK-nummer: 93721382</li><li>Vestigingsadres: Professor W.H. Keesomlaan 12, 1183 DJ Amstelveen, Nederland</li><li>Website: https://jebbari.nl</li></ul>'
            .'<h2>Kort gezegd</h2>'
            .'<p>We verzamelen alleen wat nodig is voor wat je zelf aanvraagt. Er zijn geen tracking- of advertentiecookies, geen Google Analytics en je gegevens worden nooit verkocht. De website zelf slaat je IP-adres niet op; waar dat nodig is tegen misbruik bewaren we alleen een onomkeerbaar versleutelde code ervan. Zoals bij vrijwel elke website houdt de webserver wel korte tijd technische logbestanden bij, met onder meer IP-adressen, voor beveiliging en het oplossen van storingen.</p>'
            .'<h2>Contactformulier</h2>'
            .'<p>Wat: je naam, e-mailadres, bedrijfsnaam en budgetindicatie (als je die invult) en je bericht. Waarom: om je vraag te beantwoorden en eventueel een opdracht voor te bereiden. Hoe lang: maximaal 12 maanden na het laatste contact, tenzij langer nodig is voor een overeenkomst of een wettelijke plicht.</p>'
            .'<h2>Website-check en website-watch</h2>'
            .'<p>Bij de gratis website-check geef je het adres van een webpagina en je e-mailadres op, en je geeft toestemming voor de check. Je kunt er ook voor kiezen dat ik contact met je opneem; dan wordt je check een aanvraag (zie hieronder). Het rapport en je e-mailadres worden na 90 dagen automatisch verwijderd.</p>'
            .'<p>Voor de snelheidsmeting wordt het adres van de gecontroleerde pagina naar Google PageSpeed Insights gestuurd. Google krijgt alleen dat adres, niet je e-mailadres of andere gegevens.</p>'
            .'<p>Zet je de website-watch aan, dan bewaren we je e-mailadres, het adres van je website en de laatste scores, zodat je elke maand een nieuwe check met de verschillen krijgt. Dat gebeurt pas nadat je het via de link in je e-mail hebt bevestigd; een niet-bevestigde aanmelding wordt na 7 dagen verwijderd. Elke e-mail bevat een afmeldlink. Na afmelden worden je gegevens direct verwijderd. De maandelijkse rapporten zelf worden, net als andere checks, na 90 dagen verwijderd.</p>'
            .'<h2>Prijscalculator, aanvragen en afspraken</h2>'
            .'<p>In de prijscalculator en het aanvraagformulier bewaren we je keuzes en antwoorden. Laat je geen contactgegevens achter, dan wordt een onafgemaakte aanvraag na 30 dagen automatisch verwijderd. Laat je wel gegevens achter (naam, e-mailadres en eventueel telefoonnummer en bedrijfsnaam), dan gebruiken we die om je aanvraag te behandelen en bewaren we ze maximaal 12 maanden na het laatste contact, tenzij langer nodig is voor een overeenkomst.</p>'
            .'<p>Plan je een afspraak, dan bewaren we je naam, e-mailadres, eventueel je telefoonnummer, het soort gesprek en het tijdstip. Je krijgt een bevestiging en een herinnering per e-mail. Afspraken worden een jaar na de datum van het gesprek automatisch verwijderd.</p>'
            .'<h2>Voorstellen</h2>'
            .'<p>Krijg je een persoonlijk voorstel, dan staat dat op een privé-link met je naam, e-mailadres en eventueel bedrijfsnaam. Om te weten of het voorstel is ontvangen, zien we of en wanneer de link is geopend. Geef je akkoord, dan leggen we als bewijs vast welke naam je hebt ingevuld, het tijdstip en een versleutelde code van je IP-adres. Voorstellen bewaren we zolang dat nodig is voor de overeenkomst en de wettelijke bewaartermijn voor de administratie.</p>'
            .'<h2>Nieuwsbrief</h2>'
            .'<p>Meld je je aan voor de nieuwsbrief, dan bewaren we je e-mailadres en het moment waarop je de aanmelding hebt bevestigd. Je kunt je altijd afmelden via de link in elke e-mail.</p>'
            .'<h2>Cookies en statistieken</h2>'
            .'<p>Deze website gebruikt geen tracking- of advertentiecookies en geen externe analyseplatforms. Er worden alleen functionele cookies gebruikt, die formulieren beveiligen en je sessie bijhouden. Je keuze voor een licht of donker thema wordt alleen in je eigen browser bewaard.</p>'
            .'<p>We tellen zelf hoe vaak een pagina wordt bekeken: per pagina en per dag een getal, zonder cookies, zonder IP-adres en zonder iets wat naar jou herleidbaar is. Stuurt je browser "Do Not Track" of "Global Privacy Control" mee, dan tellen we je bezoek niet.</p>'
            .'<h2>Andere partijen</h2>'
            .'<p>De website draait op een eigen VPS-server. Voor het versturen van e-mail en het hosten worden externe dienstverleners gebruikt; met partijen die namens ons persoonsgegevens verwerken sluiten we waar nodig een verwerkersovereenkomst.</p>'
            .'<p>Op de formulieren van de website-check en de aanvragen kan Cloudflare Turnstile worden gebruikt om spam tegen te houden. Alleen op die formulieren, en alleen als het is ingeschakeld, maakt je browser dan verbinding met Cloudflare. Een live demo in een projectpagina wordt pas geladen als je daar zelf op klikt; die demo staat op de website van het betreffende project.</p>'
            .'<h2>Beveiliging</h2>'
            .'<p>We nemen passende technische en organisatorische maatregelen om persoonsgegevens te beschermen tegen verlies, misbruik, onbevoegde toegang en ongewenste openbaarmaking. De website wordt alleen via een versleutelde verbinding (HTTPS) aangeboden.</p>'
            .'<h2>Jouw rechten</h2>'
            .'<p>Je hebt het recht om je gegevens in te zien, te laten corrigeren of te laten verwijderen (voor zover wettelijk toegestaan), bezwaar te maken tegen een verwerking, je toestemming in te trekken en je gegevens over te laten dragen. Mail daarvoor naar info@jebbari.nl. Je kunt ook een klacht indienen bij de Autoriteit Persoonsgegevens.</p>'
            .'<h2>Contact</h2>'
            .'<p>Onholland B.V., handelend als Jebbari<br>Professor W.H. Keesomlaan 12, 1183 DJ Amstelveen, Nederland<br>E-mail: info@jebbari.nl</p>'
            .'<p>Deze privacyverklaring kan worden aangepast als de dienstverlening of de wetgeving verandert. Laatst bijgewerkt: november 2026.</p>',

            '<h2>Who is responsible?</h2>'
            .'<p>Onholland B.V., operating under the trade name Jebbari, is responsible for the processing of personal data through this website.</p>'
            .'<ul><li>Company name: Onholland B.V.</li><li>Trade name: Jebbari</li><li>Chamber of Commerce (KvK) number: 93721382</li><li>Registered address: Professor W.H. Keesomlaan 12, 1183 DJ Amstelveen, the Netherlands</li><li>Website: https://jebbari.nl</li></ul>'
            .'<h2>In short</h2>'
            .'<p>We only collect what is needed for what you ask for yourself. There are no tracking or advertising cookies, no Google Analytics, and your data is never sold. The website itself does not store your IP address; where it is needed against misuse, we only keep an irreversibly encrypted code of it. As with almost any website, the web server does keep technical log files for a short time, including IP addresses, for security and troubleshooting.</p>'
            .'<h2>Contact form</h2>'
            .'<p>What: your name, email address, company name and budget indication (if you fill them in) and your message. Why: to answer your question and, where relevant, prepare an assignment. How long: at most 12 months after the last contact, unless longer is needed for an agreement or a legal obligation.</p>'
            .'<h2>Website check and website watch</h2>'
            .'<p>For the free website check you enter the address of a web page and your email address, and you consent to the check. You can also choose to let me contact you; your check then becomes a request (see below). The report and your email address are deleted automatically after 90 days.</p>'
            .'<p>To measure speed, the address of the checked page is sent to Google PageSpeed Insights. Google receives only that address, not your email address or any other data.</p>'
            .'<p>If you turn on the website watch, we keep your email address, your website\'s address and the latest scores, so you receive a new check with the differences every month. This only starts after you confirm it through the link in your email; an unconfirmed sign-up is deleted after 7 days. Every email contains an unsubscribe link. When you unsubscribe, your data is deleted immediately. The monthly reports themselves are deleted after 90 days, like other checks.</p>'
            .'<h2>Price calculator, requests and appointments</h2>'
            .'<p>In the price calculator and the request form we keep your choices and answers. If you leave no contact details, an unfinished request is deleted automatically after 30 days. If you do (name, email address and possibly phone number and company name), we use them to handle your request and keep them for at most 12 months after the last contact, unless longer is needed for an agreement.</p>'
            .'<p>If you book an appointment, we keep your name, email address, possibly your phone number, the type of meeting and the time. You receive a confirmation and a reminder by email. Appointments are deleted automatically one year after the date of the meeting.</p>'
            .'<h2>Proposals</h2>'
            .'<p>If you receive a personal proposal, it is on a private link with your name, email address and possibly your company name. To know whether it arrived, we see whether and when the link was opened. If you accept it, we record as proof the name you entered, the time and an encrypted code of your IP address. Proposals are kept as long as needed for the agreement and the statutory retention period for the accounts.</p>'
            .'<h2>Newsletter</h2>'
            .'<p>If you sign up for the newsletter, we keep your email address and the moment you confirmed the sign-up. You can unsubscribe at any time through the link in every email.</p>'
            .'<h2>Cookies and statistics</h2>'
            .'<p>This website uses no tracking or advertising cookies and no external analytics platforms. Only functional cookies are used, which protect forms and keep your session. Your choice of a light or dark theme is stored only in your own browser.</p>'
            .'<p>We count page views ourselves: one number per page per day, without cookies, without IP addresses and without anything that can be traced back to you. If your browser sends "Do Not Track" or "Global Privacy Control", your visit is not counted.</p>'
            .'<h2>Other parties</h2>'
            .'<p>The website runs on our own VPS server. External service providers are used for hosting and sending email; where required we enter into a data processing agreement with parties that process personal data on our behalf.</p>'
            .'<p>Cloudflare Turnstile may be used on the website check and request forms to stop spam. Only on those forms, and only when it is switched on, does your browser then connect to Cloudflare. A live demo on a project page only loads when you click it; that demo runs on the website of the project concerned.</p>'
            .'<h2>Security</h2>'
            .'<p>We take appropriate technical and organisational measures to protect personal data against loss, misuse, unauthorised access and unwanted disclosure. The website is only served over an encrypted connection (HTTPS).</p>'
            .'<h2>Your rights</h2>'
            .'<p>You have the right to access your data, have it corrected or deleted (where legally permitted), object to processing, withdraw your consent and have your data transferred. Email info@jebbari.nl to do so. You can also lodge a complaint with the Dutch Data Protection Authority (Autoriteit Persoonsgegevens).</p>'
            .'<h2>Contact</h2>'
            .'<p>Onholland B.V., trading as Jebbari<br>Professor W.H. Keesomlaan 12, 1183 DJ Amstelveen, the Netherlands<br>Email: info@jebbari.nl</p>'
            .'<p>This privacy statement may be updated when the services or the law change. Last updated: November 2026.</p>',
        );

        $seo = $page->withDraft()->seo ?? [];
        $seo['description'] = $this->t(
            'Welke gegevens jebbari.nl verwerkt, waarom en hoe lang: contact, website-check, aanvragen, afspraken, voorstellen en nieuwsbrief. Geen trackingcookies.',
            'What data jebbari.nl processes, why and for how long: contact, website check, requests, appointments, proposals and newsletter. No tracking cookies.',
        );

        $page->saveFromForm([
            'blocks' => [$this->block('rich_text', ['label' => $this->t('', ''), 'body' => $body])],
            'seo' => $seo,
        ]);
        self::$report[] = 'Privacy: volledige verklaring bijgewerkt (alle formulieren en bewaartermijnen)'.$this->state($page);
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
