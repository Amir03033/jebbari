<?php

namespace Database\Seeders;

use App\Enums\PublishStatus;
use App\Models\Project;
use Illuminate\Database\Seeder;

/**
 * Proposed case studies for the owner's own products, as drafts. Part of
 * `php artisan content:propose`, and safe to run on its own on the live site:
 *
 *   php artisan db:seed --class=ProjectProposalsSeeder --force
 *
 * It only adds projects that do not exist yet (by slug) and never publishes.
 */
class ProjectProposalsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::propose() ?: ['nothing to add: the projects already exist'] as $line) {
            $this->command?->info($line);
        }
    }

    /** @return list<string> what was created */
    public static function propose(): array
    {
        $report = [];

        foreach (self::projects() as $slug => $data) {
            if (Project::query()->where('slug_en', $slug)->exists()) {
                continue;
            }

            Project::create($data + ['is_featured' => true, 'status' => PublishStatus::Draft]);
            $report[] = 'project '.$data['title']['en'].': aangemaakt als concept (uit de GitHub-repository)';
        }

        return $report;
    }

    /**
     * DNSCheck and StackFinder, described from their own repositories (README,
     * PLAN.md, docs/01-product.md, routes, composer/package files) as of
     * November 2026. No results or numbers beyond what the repositories state;
     * the outcome line stays empty for the owner.
     *
     * @return array<string, array<string, mixed>>
     */
    private static function projects(): array
    {
        $t = fn (string $nl, string $en) => self::t($nl, $en);
        $lines = fn (array $nl, array $en) => self::t(implode("\n", $nl), implode("\n", $en));

        return [
            'dnscheck' => [
                'title' => $t('DNSCheck', 'DNSCheck'),
                'kind' => $t('DNS- en e-maildiagnose', 'DNS and email diagnostics'),
                'year' => 2026,
                'sort' => 30,
                'url' => 'https://dnscheck.info',
                'summary' => $t(
                    'Gratis online tools voor DNS en e-mail: DNS lookup, MX, SPF, DKIM, DMARC, een e-mailbeveiligingsrapport met score en een propagatiecheck.',
                    'Free online tools for DNS and email: DNS lookup, MX, SPF, DKIM, DMARC, an email security report with a score and a propagation checker.',
                ),
                'brief' => $t(
                    'Wie wil weten waarom e-mail in de spam belandt of waarom een DNS-wijziging nog niet zichtbaar is, krijgt bij de meeste tools ruwe uitvoer vol jargon, verspreid over losse pagina\'s. DNSCheck brengt dat samen in één helder rapport: wat er mis is, waarom het ertoe doet en wat je moet aanpassen.',
                    'If you want to know why email lands in spam, or why a DNS change is not visible yet, most tools give you raw output full of jargon, spread over separate pages. DNSCheck brings it together in one clear report: what is wrong, why it matters and what to change.',
                ),
                'objectives' => $lines([
                    'Eén samenhangend rapport per domein in plaats van losse tools',
                    'Uitkomsten in gewone taal, met een concrete oplossing per bevinding',
                    'Correct volgens de RFC\'s en gecontroleerd tegen een validerende resolver',
                    'Gratis te gebruiken, zonder account',
                ], [
                    'One coherent report per domain instead of separate tools',
                    'Results in plain language, with a concrete fix for every finding',
                    'Correct to the RFCs and checked against a validating resolver',
                    'Free to use, without an account',
                ]),
                'built' => $t(
                    '<p>Een Laravel-applicatie met Inertia en React voor de site en het beheer, en een eigen DNS-engine in Go. De engine doet de resolutie zelf, van de rootservers tot de authoritative nameservers, en valideert DNSSEC van de rootsleutels tot het antwoord, inclusief NSEC en NSEC3. De DNS-functies van PHP kunnen dat niet.</p><p>In de eerste proef met 23 domeinen, zes keer achter elkaar, week de engine geen enkele keer af van de validerende resolver 1.1.1.1, ook niet bij domeinen met bewust kapotte DNSSEC.</p><p>Er is ook een betaalde JSON API met abonnementen via Mollie. Die is gebouwd, maar nog niet openbaar.</p>',
                    '<p>A Laravel application with Inertia and React for the site and the admin, and a DNS engine of its own, written in Go. The engine does the resolution itself, from the root servers to the authoritative name servers, and validates DNSSEC from the root keys to the answer, including NSEC and NSEC3. PHP\'s own DNS functions cannot do that.</p><p>In the first trial with 23 domains, six runs in a row, the engine did not differ once from the validating resolver 1.1.1.1, not even for domains with deliberately broken DNSSEC.</p><p>There is also a paid JSON API with plans billed through Mollie. It is built, but not public yet.</p>',
                ),
                'features' => $lines([
                    'DNS lookup en MX-controle',
                    'SPF, DKIM en DMARC controleren',
                    'E-mailbeveiligingsrapport met score',
                    'Propagatiecheck voor DNS-wijzigingen',
                    'DNSSEC-validatie met een eigen engine in Go',
                    'JSON API met abonnementen (nog niet openbaar)',
                ], [
                    'DNS lookup and MX check',
                    'SPF, DKIM and DMARC checks',
                    'Email security report with a score',
                    'Propagation checker for DNS changes',
                    'DNSSEC validation with an engine of its own in Go',
                    'JSON API with plans (not public yet)',
                ]),
                'stack' => ['Laravel', 'Inertia', 'React', 'TypeScript', 'Tailwind CSS', 'Filament', 'Go', 'Mollie'],
            ],

            'stackfinder' => [
                'title' => $t('StackFinder', 'StackFinder'),
                'kind' => $t('Technologie-analyse van websites', 'Website technology analysis'),
                'year' => 2026,
                'sort' => 40,
                'url' => 'https://stackfinder.nl',
                'summary' => $t(
                    'Voer een website in en zie op welke technologie hij draait, met bewijs en een betrouwbaarheidsscore bij elke bevinding.',
                    'Enter a website and see the technology it runs on, with evidence and a confidence score for every finding.',
                ),
                'brief' => $t(
                    'Bestaande tools vertellen wel welke technologie een site gebruikt, maar zelden waarom ze dat denken of hoe zeker ze zijn. StackFinder laat bij elke bevinding het bewijs zien en zegt eerlijk hoe zeker het is: bevestigd, waarschijnlijk of mogelijk.',
                    'Existing tools tell you which technology a site uses, but rarely why they think so or how sure they are. StackFinder shows the evidence for every finding and says honestly how sure it is: confirmed, likely or possible.',
                ),
                'objectives' => $lines([
                    'Geen bevinding zonder bewijs dat je kunt inzien',
                    'Eerlijk over onzekerheid, in drie niveaus',
                    'Alleen bekijken wat een gewoon browserbezoek ook ziet: geen scans op kwetsbaarheden, geen inlogpogingen',
                    'Meer dan een lijstje: vergelijken, geschiedenis en export',
                ], [
                    'No finding without evidence you can inspect',
                    'Honest about uncertainty, in three levels',
                    'Only look at what a normal browser visit sees: no vulnerability scans, no login attempts',
                    'More than a list: comparison, history and export',
                ]),
                'built' => $t(
                    '<p>Een Laravel-applicatie met Inertia, React 19 en TypeScript, en een aparte scanner in Node.js (Fastify). De scanner draait in een afgeschermd netwerk en komt alleen via een egress-proxy (Smokescreen) bij het internet, zodat niemand hem kan gebruiken om interne adressen te bereiken.</p><p>Technologieën worden herkend met signatures in YAML, elk met testvoorbeelden en een validator. Scans lopen via een wachtrij met Redis en Horizon.</p>',
                    '<p>A Laravel application with Inertia, React 19 and TypeScript, and a separate scanner in Node.js (Fastify). The scanner runs in a closed-off network and reaches the internet only through an egress proxy (Smokescreen), so nobody can use it to reach internal addresses.</p><p>Technologies are recognised with signatures in YAML, each with test fixtures and a validator. Scans run through a queue with Redis and Horizon.</p>',
                ),
                'features' => $lines([
                    'Rapport met bewijs en betrouwbaarheid per technologie',
                    'Twee websites naast elkaar vergelijken',
                    'Accounts met geschiedenis en favorieten',
                    'Inloggen met Google of GitHub',
                    'Export als JSON of CSV, en een API',
                    'Pagina\'s per technologie en per categorie',
                    'Eigenaren kunnen hun site laten verwijderen',
                ], [
                    'Report with evidence and confidence for every technology',
                    'Compare two websites side by side',
                    'Accounts with history and favourites',
                    'Sign in with Google or GitHub',
                    'Export as JSON or CSV, and an API',
                    'Pages per technology and per category',
                    'Owners can have their site removed',
                ]),
                'stack' => ['Laravel', 'Inertia', 'React', 'TypeScript', 'Tailwind CSS', 'Node.js', 'Fastify', 'MySQL', 'Redis', 'Docker'],
            ],
        ];
    }

    private static function t(string $nl, string $en): array
    {
        return ['nl' => $nl, 'en' => $en];
    }
}
