<?php

namespace Database\Seeders;

use App\Enums\PublishStatus;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Database\Seeder;

/**
 * Proposed content for the personal-brand site: every page, Dutch and English.
 *
 * Nothing here goes live by itself. A page that is already published gets the
 * proposal as "unpublished changes" (review, then publish or discard); a page
 * that is still a draft is filled in; a missing page is created as a draft.
 *
 * By default existing sections are left alone and only missing ones are added.
 * With $replace the whole page is proposed anew (`php artisan content:propose --replace`).
 *
 * Facts only the owner knows are marked "[[ OWNER: ... ]]". The CMS refuses to
 * publish a page that still contains one, so nothing invented can reach visitors.
 */
class PersonalBrandSeeder extends Seeder
{
    public static bool $replace = false;

    /** Also publish every page that has nothing left to fill in. Only on the owner's explicit request. */
    public static bool $publish = false;

    /** @var list<string> what happened, for the command's report */
    public static array $report = [];

    public function run(): void
    {
        self::$report = [];

        foreach ($this->pages() as $key => [$title, $blocks]) {
            $this->propose($key, $title, $blocks);
        }

        $this->projects();
        $this->navigation();

        if (self::$publish) {
            $this->publishComplete(array_keys($this->pages()));
        }
    }

    /** Publishes the pages without open issues; pages with an owner placeholder or a gap stay drafts and are reported. */
    private function publishComplete(array $keys): void
    {
        foreach (Page::query()->whereIn('key', $keys)->get() as $page) {
            $issues = $page->publishIssues();

            if ($issues) {
                self::$report[] = $page->key.': NIET gepubliceerd ('.count($issues).' open punt(en), bijvoorbeeld: '.$issues[0].')';

                continue;
            }

            if (! $page->isPublished() || $page->hasDraftChanges()) {
                $page->publish();
                self::$report[] = $page->key.': gepubliceerd';
            }
        }
    }

    private function propose(string $key, array $title, array $blocks): void
    {
        $page = Page::query()->where('key', $key)->first();

        if (! $page) {
            Page::create(['key' => $key, 'kind' => 'system', 'title' => $title, 'blocks' => $blocks, 'status' => PublishStatus::Draft]);
            self::$report[] = "$key: aangemaakt als concept";

            return;
        }

        $current = $page->withDraft();
        $merged = self::$replace ? $blocks : $this->merge($current->blocks ?? [], $blocks);
        $added = count($merged) - count($current->blocks ?? []);

        $page->saveFromForm(['title' => self::$replace ? $title : $current->title, 'blocks' => $merged]);

        self::$report[] = $key.': '.(self::$replace ? 'volledig voorstel' : ($added > 0 ? "$added onderdeel/onderdelen toegevoegd" : 'ongewijzigd'))
            .($page->isPublished() ? ' (als niet-gepubliceerde wijziging)' : ' (concept)');
    }

    /** Keep what is there; add proposed sections of a type the page does not have yet, before the closing section. */
    private function merge(array $existing, array $proposed): array
    {
        $types = array_column($existing, 'type');
        $new = array_values(array_filter($proposed, fn ($b) => ! in_array($b['type'], $types, true)));

        $closing = array_search('closing_cta', $types, true);

        if ($closing === false) {
            return [...$existing, ...$new];
        }

        return [...array_slice($existing, 0, $closing), ...$new, ...array_slice($existing, $closing)];
    }

    private function b(string $type, array $data = [], bool $visible = true): array
    {
        return ['type' => $type, 'data' => ['visible' => $visible, ...$data]];
    }

    private function t(string $nl, string $en): array
    {
        return ['nl' => $nl, 'en' => $en];
    }

    /** @return array<string, array{0: array, 1: list<array>}> */
    private function pages(): array
    {
        $t = fn (string $nl, string $en) => $this->t($nl, $en);

        return [
            'home' => [$t('Home', 'Home'), [
                $this->b('hero', [
                    'label' => $t('Softwareontwikkelaar & ondernemer', 'Software developer & entrepreneur'),
                    'headline' => $t("Digitale producten bouwen.\nMaken wat hierna komt", "Building digital experiences.\nCreating what comes next"),
                    'end_with_stop' => true,
                    'description' => $t(
                        'Ik ben Jebbari. Ik ontwerp en bouw software, van eerste idee tot product dat draait, en werk daarnaast aan eigen projecten.',
                        'I am Jebbari. I design and build software, from first idea to running product, and work on projects of my own.',
                    ),
                    'cta_label' => $t('Bekijk mijn werk', 'Explore my work'), 'cta_page' => 'work',
                    'cta2_label' => $t('Neem contact op', 'Get in touch'), 'cta2_page' => 'contact',
                ]),
                $this->b('work_selection', [
                    'label' => $t('Werk', 'Work'), 'mode' => 'featured', 'limit' => 4,
                    'intro' => $t('Een selectie van producten die ik zelf heb bedacht, gebouwd en draaiend houd.', 'A selection of products I designed, built and keep running myself.'),
                ]),
                $this->b('services_overview', [
                    'label' => $t('Diensten', 'Services'), 'mode' => 'all',
                    'intro' => $t('Vier manieren waarop ik kan helpen, los of gecombineerd.', 'Four ways I can help, on their own or combined.'),
                ]),
                $this->b('tech_marquee'),
                $this->b('process', [
                    'label' => $t('Werkwijze', 'Process'),
                    'heading' => $t('Zo werk ik', 'How I work'),
                    'steps' => [
                        ['title' => $t('Begrijpen', 'Understand'), 'description' => $t('Eerst het probleem, dan pas de oplossing. Ik stel de lastige vragen vooraf.', 'The problem first, the solution second. I ask the awkward questions up front.')],
                        ['title' => $t('Ontwerpen', 'Design'), 'description' => $t('Een klikbaar ontwerp dat je kunt beoordelen voordat er code staat.', 'A clickable design you can judge before any code exists.')],
                        ['title' => $t('Bouwen', 'Build'), 'description' => $t('Schone architectuur, werkende tussenversies, geen verrassingen.', 'Clean architecture, working builds along the way, no surprises.')],
                        ['title' => $t('Verbeteren', 'Improve'), 'description' => $t('Live zetten, meten, bijsturen. Software is niet af bij de lancering.', 'Ship, measure, adjust. Software is not finished at launch.')],
                    ],
                ]),
                $this->b('about_teaser', [
                    'label' => $t('Over mij', 'About'),
                    'heading' => $t('Ontwikkelaar van beroep, ondernemer van nature.', 'Developer by trade, entrepreneur by nature.'),
                    'body' => $t(
                        "Ik bouw software omdat ik wil zien dat een idee echt werkt.\n\nNaast werk voor anderen bouw ik eigen producten, zoals ServerScope en ChinaSupply. Dat houdt me scherp: ik weet hoe het is om zelf eigenaar te zijn van wat je bouwt.",
                        "I build software because I want to see an idea actually work.\n\nNext to work for others I build products of my own, such as ServerScope and ChinaSupply. It keeps me sharp: I know what it is like to own what you build.",
                    ),
                ]),
                $this->b('github'),
                $this->b('faq', [
                    'label' => $t('Veelgestelde vragen', 'Questions'),
                    'heading' => $t('Kort antwoord op wat mensen het vaakst vragen.', 'Short answers to what people ask most.'),
                    'items' => [
                        ['question' => $t('Met wie werk ik samen?', 'Who am I working with?'), 'answer' => $t('Met mij, van het eerste gesprek tot de oplevering. Er zit geen accountmanager of doorverwijzing tussen.', 'With me, from the first conversation to delivery. There is no account manager or hand-off in between.')],
                        ['question' => $t('Hoe begint een project?', 'How does a project start?'), 'answer' => $t('Met een gesprek over wat je wilt bereiken. Daarna leg ik de afspraken over wat ik bouw en wat het kost schriftelijk vast, voordat er iets gebouwd wordt.', 'With a conversation about what you want to achieve. After that I put in writing what I will build and what it costs, before anything is built.')],
                        ['question' => $t('Kan ik de site of applicatie daarna zelf beheren?', 'Can I manage the site or application myself afterwards?'), 'answer' => $t('Ja. Tekst en beeld pas je zelf aan in een beheeromgeving; de code is van jou.', 'Yes. You change text and images yourself in an admin environment; the code is yours.')],
                        ['question' => $t('Wat gebeurt er na de oplevering?', 'What happens after delivery?'), 'answer' => $t('Ik kan updates, monitoring en back-ups blijven verzorgen. Dat spreken we apart af, zodat je weet waar je aan toe bent.', 'I can keep taking care of updates, monitoring and backups. We agree on that separately, so you know where you stand.')],
                    ],
                ]),
                $this->b('newsletter', [], false),
                $this->b('closing_cta', [
                    'headline' => $t("Iets in gedachten?\nLaten we het bouwen", "Have something in mind?\nLet’s build it"),
                    'booking_label' => $t('Plan een gesprek', 'Book a call'),
                ]),
            ]],

            'about' => [$t('Over mij', 'About'), [
                $this->b('intro', [
                    'label' => $t('Over mij', 'About'),
                    'heading' => $t('Ik maak software die klopt, en bedrijven eromheen.', 'I build software that holds up, and businesses around it.'),
                    'body' => $t('Softwareontwikkelaar, ondernemer en technologieliefhebber.', 'Software developer, entrepreneur and technology enthusiast.'),
                ]),
                $this->b('biography', [
                    'label' => $t('Achtergrond', 'Background'),
                    'body' => $t(
                        '<p>[[ OWNER: hoe en wanneer je bent begonnen met programmeren ]]</p><p>Ik werk het liefst aan dingen die echt gebruikt worden: websites, webapplicaties en eigen producten zoals ServerScope en ChinaSupply. Ik ontwerp, bouw en beheer ze zelf, waardoor ik het hele traject overzie.</p><p>[[ OWNER: waar je als ondernemer naartoe wilt ]]</p>',
                        '<p>[[ OWNER: how and when you started programming ]]</p><p>I prefer working on things that are really used: websites, web applications and products of my own such as ServerScope and ChinaSupply. I design, build and run them myself, so I oversee the whole path.</p><p>[[ OWNER: where you want to go as an entrepreneur ]]</p>',
                    ),
                ]),
                $this->b('skills', [
                    'label' => $t('Vaardigheden', 'Skills'),
                    'items' => [
                        ['name' => $t('Backend', 'Backend'), 'description' => $t('PHP en Laravel, Node.js, MySQL. Van datamodel tot API.', 'PHP and Laravel, Node.js, MySQL. From data model to API.')],
                        ['name' => $t('Frontend', 'Frontend'), 'description' => $t('JavaScript, Vue.js, HTML, CSS en Tailwind CSS. Snel, toegankelijk en verzorgd.', 'JavaScript, Vue.js, HTML, CSS and Tailwind CSS. Fast, accessible and well finished.')],
                        ['name' => $t('Servers en beheer', 'Servers and operations'), 'description' => $t('Linux, serverbeheer, Git en deployment. Ik zet zelf live wat ik bouw.', 'Linux, server management, Git and deployment. I ship what I build myself.')],
                        ['name' => $t('Beveiliging', 'Security'), 'description' => $t('Veilig bouwen vanaf de eerste regel: strikte headers, geen onnodige afhankelijkheden, updates bijhouden.', 'Building securely from the first line: strict headers, no needless dependencies, keeping up with updates.')],
                    ],
                ]),
                $this->b('experience', [
                    'label' => $t('Ervaring', 'Experience'),
                    'items' => [
                        ['period' => '[[ OWNER: periode ]]', 'organisation' => 'Jebbari', 'role' => $t('Oprichter en ontwikkelaar', 'Founder and developer'), 'description' => $t('Websites, webapplicaties en eigen producten, van ontwerp tot beheer.', 'Websites, web applications and products of my own, from design to operations.')],
                    ],
                ]),
                $this->b('principles', [
                    'label' => $t('Aanpak', 'Approach'),
                    'items' => [
                        ['name' => $t('Doordacht bouwen, niet snel plakken.', 'Thoughtful engineering, not quick patches.')],
                        ['name' => $t('Snelheid is een functie.', 'Performance is a feature.')],
                        ['name' => $t('Veiligheid vanaf de eerste regel.', 'Security from the first line.')],
                        ['name' => $t('De gebruiker merkt elk detail.', 'Users notice every detail.')],
                        ['name' => $t('Blijven verbeteren na de lancering.', 'Keep improving after launch.')],
                    ],
                ]),
                $this->b('github'),
            ]],

            'services' => [$t('Diensten', 'Services'), [
                $this->b('intro', [
                    'label' => $t('Diensten', 'Services'),
                    'heading' => $t('Wat ik voor je kan bouwen.', 'What I can build for you.'),
                    'body' => $t('Van een snelle website tot een applicatie op maat, en het beheer daarna.', 'From a fast website to a custom application, and looking after it afterwards.'),
                ]),
                $this->b('service_list'),
                $this->b('packages_table'),
                $this->b('faq', [
                    'label' => $t('Veelgestelde vragen', 'Questions'),
                    'items' => [
                        ['question' => $t('Wat kost een project?', 'What does a project cost?'), 'answer' => $t('Dat hangt af van wat er gebouwd moet worden. Na een eerste gesprek krijg je een voorstel met een vaste omschrijving en prijs, zodat je vooraf weet waar je aan toe bent.', 'That depends on what needs to be built. After a first conversation you get a proposal with a fixed description and price, so you know where you stand beforehand.')],
                        ['question' => $t('Hoe lang duurt het?', 'How long does it take?'), 'answer' => $t('Een website is doorgaans sneller klaar dan een applicatie op maat. In het voorstel staat een planning; tussentijds zie je werkende versies.', 'A website is generally finished sooner than a custom application. The proposal contains a schedule; along the way you see working versions.')],
                        ['question' => $t('Met welke techniek werk je?', 'Which technology do you use?'), 'answer' => $t('Meestal Laravel en PHP aan de achterkant en Tailwind CSS met JavaScript aan de voorkant. Ik kies wat past bij het probleem, niet andersom.', 'Mostly Laravel and PHP on the back end and Tailwind CSS with JavaScript on the front end. I choose what fits the problem, not the other way round.')],
                        ['question' => $t('Neem je ook bestaande projecten over?', 'Do you take over existing projects?'), 'answer' => $t('Dat kan, na een korte doorlichting van de code. Zo weten we allebei wat de staat is voordat ik eraan begin.', 'I can, after a short review of the code. That way we both know its condition before I start.')],
                    ],
                ]),
            ]],

            'work' => [$t('Werk', 'Work'), [
                $this->b('intro', [
                    'label' => $t('Werk', 'Work'),
                    'heading' => $t('Projecten en producten.', 'Projects and products.'),
                    'body' => $t('Wat ik heb ontworpen en gebouwd, voor anderen en voor mezelf.', 'What I designed and built, for others and for myself.'),
                ]),
            ]],

            'blog' => [$t('Blog', 'Blog'), [
                $this->b('intro', [
                    'label' => $t('Blog', 'Blog'),
                    'heading' => $t('Notities over bouwen.', 'Notes on building.'),
                    'body' => $t('Over software, servers en ondernemen: wat ik onderweg leer.', 'On software, servers and running a business: what I learn along the way.'),
                ]),
                $this->b('newsletter', [], false),
            ]],

            'contact' => [$t('Contact', 'Contact'), [
                $this->b('intro', [
                    'label' => $t('Contact', 'Contact'),
                    'heading' => $t('Vertel wat je wilt bouwen.', 'Tell me what you want to build.'),
                    'body' => $t('Een idee, een vraag of een lopend project: stuur een bericht en ik denk met je mee.', 'An idea, a question or a running project: send a message and I will think along.'),
                ]),
                $this->b('reply_promise', ['text' => $t('Ik reageer binnen één werkdag.', 'I reply within one working day.')]),
                $this->b('meeting_line', ['text' => $t('Afspreken kan online of op locatie.', 'We can meet online or in person.')]),
                $this->b('faq', [
                    'label' => $t('Goed om te weten', 'Good to know'),
                    'items' => [
                        ['question' => $t('Wat gebeurt er met mijn bericht?', 'What happens to my message?'), 'answer' => $t('Het komt rechtstreeks bij mij terecht. Ik gebruik je gegevens alleen om te antwoorden.', 'It reaches me directly. I use your details only to reply.')],
                        ['question' => $t('Moet ik al precies weten wat ik wil?', 'Do I need to know exactly what I want?'), 'answer' => $t('Nee. Een ruwe schets van het probleem is genoeg om het gesprek te beginnen.', 'No. A rough sketch of the problem is enough to start the conversation.')],
                        ['question' => $t('Kost een eerste gesprek iets?', 'Does a first conversation cost anything?'), 'answer' => $t('Nee, kennismaken is vrijblijvend.', 'No, getting acquainted is without obligation.')],
                    ],
                ]),
            ]],

            'now' => [$t('Nu', 'Now'), [
                $this->b('intro', [
                    'label' => $t('Nu', 'Now'),
                    'heading' => $t('Waar ik nu mee bezig ben.', 'What I am working on now.'),
                    'body' => $t('Een korte stand van zaken, die ik bijwerk als er iets verandert.', 'A short status, updated when something changes.'),
                ]),
                $this->b('focus', [
                    'label' => $t('Nu mee bezig', 'Current focus'),
                    'items' => [
                        ['name' => $t('ServerScope', 'ServerScope'), 'description' => $t('[[ OWNER: waar je nu aan bouwt bij ServerScope ]]', '[[ OWNER: what you are building on ServerScope right now ]]')],
                        ['name' => $t('ChinaSupply', 'ChinaSupply'), 'description' => $t('[[ OWNER: waar je nu aan bouwt bij ChinaSupply ]]', '[[ OWNER: what you are building on ChinaSupply right now ]]')],
                        ['name' => $t('Werk voor anderen', 'Work for others'), 'description' => $t('Websites en webapplicaties op maat. Zie de statusregel onderaan elke pagina voor mijn beschikbaarheid.', 'Custom websites and web applications. See the status line at the bottom of every page for my availability.')],
                        ['name' => $t('Leren', 'Learning'), 'description' => $t('[[ OWNER: wat je nu leert of uitprobeert ]]', '[[ OWNER: what you are learning or trying out ]]')],
                    ],
                ]),
                $this->b('github'),
            ]],

            'uses' => [$t('Stack', 'Uses'), [
                $this->b('intro', [
                    'label' => $t('Stack', 'Uses'),
                    'heading' => $t('Waarmee ik werk.', 'What I work with.'),
                    'body' => $t('De gereedschappen achter mijn projecten, van framework tot server.', 'The tools behind my projects, from framework to server.'),
                ]),
                $this->b('gear', [
                    'label' => $t('Deze website', 'This website'),
                    'items' => [
                        ['name' => $t('Laravel', 'Laravel'), 'description' => $t('Het framework onder deze site en het beheer erachter.', 'The framework under this site and the admin behind it.')],
                        ['name' => $t('Tailwind CSS', 'Tailwind CSS'), 'description' => $t('Alle opmaak, op basis van een eigen set ontwerptokens.', 'All styling, based on a small set of design tokens of my own.')],
                        ['name' => $t('Alpine.js en Vite', 'Alpine.js and Vite'), 'description' => $t('Een klein beetje script voor interactie, snel gebundeld.', 'A small amount of script for interaction, bundled fast.')],
                        ['name' => $t('Filament', 'Filament'), 'description' => $t('De beheeromgeving waarin ik deze teksten bijwerk.', 'The admin environment in which I update these texts.')],
                        ['name' => $t('Geist', 'Geist'), 'description' => $t('Het lettertype, zelf gehost. Er gaat geen verzoek naar een derde partij.', 'The typeface, self-hosted. No request goes to a third party.')],
                    ],
                ]),
                $this->b('gear', [
                    'label' => $t('Ontwikkelen', 'Development'),
                    'items' => [
                        ['name' => $t('Talen en frameworks', 'Languages and frameworks'), 'description' => $t('PHP, Laravel, JavaScript, Vue.js, Node.js, HTML en CSS.', 'PHP, Laravel, JavaScript, Vue.js, Node.js, HTML and CSS.')],
                        ['name' => $t('Data', 'Data'), 'description' => $t('MySQL.', 'MySQL.')],
                        ['name' => $t('Versiebeheer', 'Version control'), 'description' => $t('Git.', 'Git.')],
                        ['name' => $t('Editor', 'Editor'), 'description' => $t('[[ OWNER: je editor en belangrijkste extensies ]]', '[[ OWNER: your editor and main extensions ]]')],
                    ],
                ]),
                $this->b('gear', [
                    'label' => $t('Servers', 'Servers'),
                    'items' => [
                        ['name' => $t('Besturingssysteem', 'Operating system'), 'description' => $t('Linux.', 'Linux.')],
                        ['name' => $t('Hosting', 'Hosting'), 'description' => $t('[[ OWNER: waar je host en waarom ]]', '[[ OWNER: where you host and why ]]')],
                        ['name' => $t('Monitoring', 'Monitoring'), 'description' => $t('ServerScope, mijn eigen monitoring-dashboard.', 'ServerScope, my own monitoring dashboard.')],
                    ],
                ]),
                $this->b('gear', [
                    'label' => $t('Hardware', 'Hardware'),
                    'items' => [
                        ['name' => $t('Computer', 'Computer'), 'description' => $t('[[ OWNER: je computer ]]', '[[ OWNER: your computer ]]')],
                    ],
                ], false),
            ]],

            'faq' => [$t('Veelgestelde vragen', 'Questions'), [
                $this->b('intro', [
                    'label' => $t('Veelgestelde vragen', 'Questions'),
                    'heading' => $t('Vragen, en eerlijke antwoorden.', 'Questions, and straight answers.'),
                    'body' => $t('Staat je vraag er niet tussen? Stuur een bericht, dan antwoord ik zelf.', 'Is your question not here? Send a message and I will answer it myself.'),
                ]),
                $this->b('faq', [
                    'label' => $t('Samenwerken', 'Working together'),
                    'items' => [
                        ['question' => $t('Met wie werk ik samen?', 'Who am I working with?'), 'answer' => $t('Met mij, van het eerste gesprek tot de oplevering. Er zit geen accountmanager of doorverwijzing tussen.', 'With me, from the first conversation to delivery. There is no account manager or hand-off in between.')],
                        ['question' => $t('Hoe begint een project?', 'How does a project start?'), 'answer' => $t('Met een gesprek over wat je wilt bereiken. Daarna leg ik schriftelijk vast wat ik bouw en wat het kost, voordat er iets gebouwd wordt.', 'With a conversation about what you want to achieve. After that I put in writing what I will build and what it costs, before anything is built.')],
                        ['question' => $t('Moet ik al precies weten wat ik wil?', 'Do I need to know exactly what I want?'), 'answer' => $t('Nee. Een ruwe schets van het probleem is genoeg. Het uitwerken doen we samen.', 'No. A rough sketch of the problem is enough. We work it out together.')],
                        ['question' => $t('Hoe houd je me op de hoogte?', 'How do you keep me informed?'), 'answer' => $t('Je krijgt tussentijds werkende versies te zien die je zelf kunt openen en proberen. Zo zie je voortgang in plaats van beloftes.', 'You see working versions along the way that you can open and try yourself. That way you see progress instead of promises.')],
                        ['question' => $t('Kost een eerste gesprek iets?', 'Does a first conversation cost anything?'), 'answer' => $t('Nee, kennismaken is vrijblijvend.', 'No, getting acquainted is without obligation.')],
                    ],
                ]),
                $this->b('faq', [
                    'label' => $t('Prijs en planning', 'Price and planning'),
                    'items' => [
                        ['question' => $t('Wat kost een project?', 'What does a project cost?'), 'answer' => $t('Dat hangt af van wat er gebouwd moet worden. Na een eerste gesprek krijg je een voorstel met een vaste omschrijving en prijs, zodat je vooraf weet waar je aan toe bent.', 'That depends on what needs to be built. After a first conversation you get a proposal with a fixed description and price, so you know where you stand beforehand.')],
                        ['question' => $t('Hoe lang duurt het?', 'How long does it take?'), 'answer' => $t('Een website is doorgaans sneller klaar dan een applicatie op maat. In het voorstel staat een planning die bij jouw project hoort.', 'A website is generally finished sooner than a custom application. The proposal contains a schedule that fits your project.')],
                        ['question' => $t('Wat als ik onderweg iets anders wil?', 'What if I want something different along the way?'), 'answer' => $t('Dat kan. We bespreken wat het betekent voor planning en prijs, en ik ga pas verder als dat duidelijk is.', 'That is possible. We discuss what it means for schedule and price, and I only continue once that is clear.')],
                    ],
                ]),
                $this->b('faq', [
                    'label' => $t('Techniek', 'Technology'),
                    'items' => [
                        ['question' => $t('Met welke techniek werk je?', 'Which technology do you use?'), 'answer' => $t('Meestal Laravel en PHP aan de achterkant en Tailwind CSS met JavaScript aan de voorkant. Ik kies wat past bij het probleem, niet andersom.', 'Mostly Laravel and PHP on the back end and Tailwind CSS with JavaScript on the front end. I choose what fits the problem, not the other way round.')],
                        ['question' => $t('Kan ik de site of applicatie zelf beheren?', 'Can I manage the site or application myself?'), 'answer' => $t('Ja. Tekst en beeld pas je zelf aan in een beheeromgeving, in het Nederlands en zonder technische kennis.', 'Yes. You change text and images yourself in an admin environment, without technical knowledge.')],
                        ['question' => $t('Van wie is de code?', 'Who owns the code?'), 'answer' => $t('Van jou. Na oplevering heb je alles in handen om er zelf of met iemand anders mee verder te gaan.', 'You do. After delivery you have everything in hand to continue on your own or with someone else.')],
                        ['question' => $t('Neem je ook bestaande projecten over?', 'Do you take over existing projects?'), 'answer' => $t('Dat kan, na een korte doorlichting van de code. Zo weten we allebei wat de staat is voordat ik eraan begin.', 'I can, after a short review of the code. That way we both know its condition before I start.')],
                    ],
                ]),
                $this->b('faq', [
                    'label' => $t('Na de oplevering', 'After delivery'),
                    'items' => [
                        ['question' => $t('Wat gebeurt er na de oplevering?', 'What happens after delivery?'), 'answer' => $t('Ik kan updates, monitoring en back-ups blijven verzorgen. Dat spreken we apart af, zodat je weet waar je aan toe bent.', 'I can keep taking care of updates, monitoring and backups. We agree on that separately, so you know where you stand.')],
                        ['question' => $t('Wat doet deze website met mijn gegevens?', 'What does this website do with my data?'), 'answer' => $t('Zo min mogelijk. Er zijn geen trackingcookies en er worden geen gegevens met advertentienetwerken gedeeld. Een bericht via het formulier gebruik ik alleen om te antwoorden.', 'As little as possible. There are no tracking cookies and no data is shared with advertising networks. I use a message sent through the form only to reply.')],
                    ],
                ]),
            ]],

            'cv' => [$t('Cv', 'CV'), [
                $this->b('intro', [
                    'label' => $t('Cv', 'CV'),
                    'heading' => $t('Jebbari', 'Jebbari'),
                    'body' => $t('Softwareontwikkelaar en ondernemer. Ik ontwerp, bouw en beheer websites, webapplicaties en eigen producten.', 'Software developer and entrepreneur. I design, build and run websites, web applications and products of my own.'),
                ]),
            ]],
        ];
    }

    /** ChinaSupply was named by the owner; only its name is known, the rest is for the owner to add. */
    private function projects(): void
    {
        if (! Project::query()->where('slug_en', 'chinasupply')->exists() && ! Project::query()->where('title', 'like', '%ChinaSupply%')->exists()) {
            Project::create([
                'title' => $this->t('ChinaSupply', 'ChinaSupply'),
                'kind' => $this->t('[[ OWNER: soort project ]]', '[[ OWNER: kind of project ]]'),
                'is_featured' => true, 'sort' => 20, 'status' => PublishStatus::Draft,
            ]);
            self::$report[] = 'project ChinaSupply: aangemaakt als concept (alleen de naam)';
        }
    }

    /** "Over ons" becomes "Over mij" in the menus, unless the owner already renamed it. */
    private function navigation(): void
    {
        NavigationItem::query()->where('page_key', 'about')->get()
            ->filter(fn ($item) => ($item->label['nl'] ?? null) === 'Over ons')
            ->each(fn ($item) => $item->update(['label' => ['nl' => 'Over mij', 'en' => $item->label['en'] ?? 'About']]));
    }
}
