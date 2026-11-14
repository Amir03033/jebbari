<?php

namespace App\Console\Commands;

use App\Models\NavigationItem;
use App\Models\Page;
use Illuminate\Console\Command;

/**
 * Writes the "Over mij / About" text agreed with the owner into the About page
 * as a DRAFT, through the same draft rule as the admin (saveFromForm): a live
 * page keeps showing its current content until the owner publishes the changes
 * in /admin. Only facts the owner supplied; no education, ever.
 */
class DraftAboutPage extends Command
{
    protected $signature = 'cms:draft-about {--force : Replace unpublished changes that already exist}';

    protected $description = 'Put the agreed About page text (NL/EN) into the About page as a draft for review';

    public function handle(): int
    {
        $page = Page::query()->where('key', 'about')->first();

        if (! $page) {
            $this->error('There is no About page yet. Run the CMS seeder first.');

            return self::FAILURE;
        }

        if ($page->hasDraftChanges() && ! $this->option('force')) {
            $this->error('The About page already has unpublished changes. Review them in /admin, or run again with --force to replace them.');

            return self::FAILURE;
        }

        $content = self::content();
        // Keep a share image or noindex the owner may already have set.
        $content['seo'] = $content['seo'] + ($page->withDraft()->seo ?? []);

        $page->saveFromForm($content);

        // Navigation has no draft step; only rename the label if it is still the old default.
        NavigationItem::query()->where('page_key', 'about')->get()
            ->filter(fn (NavigationItem $item) => ($item->label['nl'] ?? null) === 'Over ons')
            ->each(fn (NavigationItem $item) => $item->update(['label' => ['nl' => 'Over mij'] + $item->label]));

        $this->info($page->isPublished()
            ? 'Saved as unpublished changes. Review and publish the About page in /admin.'
            : 'Saved. The About page is a draft: review and publish it in /admin.');

        return self::SUCCESS;
    }

    /** @return array{title: array, seo: array, blocks: list<array>} */
    public static function content(): array
    {
        $block = fn (string $type, array $data) => ['type' => $type, 'data' => ['visible' => true, ...$data]];

        return [
            'title' => ['nl' => 'Over mij', 'en' => 'About'],
            'seo' => [
                'title' => [
                    'nl' => 'Amir Jebbari · Oprichter van Jebbari, Amsterdam en Zwolle',
                    'en' => 'Amir Jebbari · Founder of Jebbari, Amsterdam and Zwolle',
                ],
                'description' => [
                    'nl' => 'Amir Jebbari is de oprichter van Jebbari, gevestigd in Amsterdam en Zwolle. Hij bouwt webapplicaties en websites met Laravel, Vue en TypeScript.',
                    'en' => 'Amir Jebbari is the founder of Jebbari, based in Amsterdam and Zwolle, the Netherlands. He builds web applications and websites with Laravel, Vue and TypeScript.',
                ],
            ],
            'blocks' => [
                $block('intro', [
                    'label' => ['nl' => 'Over mij', 'en' => 'About'],
                    'heading' => ['nl' => 'Amir Jebbari', 'en' => 'Amir Jebbari'],
                    'body' => [
                        'nl' => 'Amir Jebbari is de oprichter van Jebbari, gevestigd in Amsterdam en Zwolle. Hij bouwt webapplicaties en websites met Laravel, Vue en TypeScript voor klanten in heel Nederland.',
                        'en' => 'Amir Jebbari is the founder of Jebbari, based in Amsterdam and Zwolle, the Netherlands. He builds web applications and websites with Laravel, Vue and TypeScript for clients across the Netherlands.',
                    ],
                ]),
                $block('biography', [
                    'label' => ['nl' => 'Wie ik ben', 'en' => 'Who I am'],
                    'body' => [
                        'nl' => '<p>Ik ben Amir Jebbari. Ik heb Jebbari zelf opgezet en volledig zelf gebouwd. Ik woon in Zwolle en werk vanuit Zwolle en Amsterdam.</p>'
                            .'<p>Mijn vakgebied is webapplicaties en SaaS, met bijzondere aandacht voor server- en online security.</p>',
                        'en' => '<p>I am Amir Jebbari. I set up Jebbari myself and built it entirely on my own. I live in Zwolle and work from Zwolle and Amsterdam.</p>'
                            .'<p>My field is web applications and SaaS, with particular attention to server and online security.</p>',
                    ],
                ]),
                $block('biography', [
                    'label' => ['nl' => 'Wat ik doe', 'en' => 'What I do'],
                    'body' => [
                        'nl' => '<p>Ik ontwerp en bouw maatwerk webapplicaties, van boekingssystemen en dashboards tot offline-first webapps. Ik let daarbij op snelheid, een rustig en minimaal ontwerp en solide beveiliging van server en applicatie.</p>'
                            .'<p>Ik werk met Laravel (PHP), Vue 3, TypeScript, Filament, Blade en Tailwind.</p>',
                        'en' => '<p>I design and build custom web applications, from booking systems and dashboards to offline-first web apps. I focus on speed, calm and minimal design, and solid security for both server and application.</p>'
                            .'<p>I work with Laravel (PHP), Vue 3, TypeScript, Filament, Blade and Tailwind.</p>',
                    ],
                ]),
                $block('biography', [
                    'label' => ['nl' => 'Projecten', 'en' => 'Projects'],
                    'body' => [
                        'nl' => '<ul>'
                            .'<li><strong>ServerScope</strong>: een SaaS-platform om servers en online omgevingen te monitoren, gebouwd met Laravel.</li>'
                            .'<li><strong>BarberCuts</strong>: een mobile-first boekingswebapp voor een kapper, gebouwd met Laravel.</li>'
                            .'<li><strong><a href="https://amirs.life">amirs.life</a></strong>: mijn persoonlijke site en testomgeving, gebouwd met Laravel 11 en Filament 3.</li>'
                            .'</ul><p>Meer staat op de pagina <a href="/nl/werk">Werk</a>.</p>',
                        'en' => '<ul>'
                            .'<li><strong>ServerScope</strong>: a SaaS platform for monitoring servers and online environments, built with Laravel.</li>'
                            .'<li><strong>BarberCuts</strong>: a mobile-first booking web app for a barber, built with Laravel.</li>'
                            .'<li><strong><a href="https://amirs.life">amirs.life</a></strong>: my personal site and test environment, built with Laravel 11 and Filament 3.</li>'
                            .'</ul><p>More on the <a href="/en/work">Work</a> page.</p>',
                    ],
                ]),
                $block('skills', [
                    'label' => ['nl' => 'Werkwijze', 'en' => 'How I work'],
                    'items' => [
                        ['name' => ['nl' => 'Praten', 'en' => 'Talk'], 'description' => ['nl' => 'Ik luister, stel de lastige vragen en leg scope en prijs schriftelijk vast.', 'en' => 'I listen, ask the awkward questions, and agree scope and price in writing.']],
                        ['name' => ['nl' => 'Ontwerpen', 'en' => 'Design'], 'description' => ['nl' => 'Wireframes en een klikbaar ontwerp dat jij goedkeurt voordat er code wordt geschreven.', 'en' => 'Wireframes and a clickable design you approve before any code.']],
                        ['name' => ['nl' => 'Bouwen', 'en' => 'Build'], 'description' => ['nl' => 'Wekelijks werkende versies die je kunt openen en gebruiken. Geen verrassingen.', 'en' => 'Weekly working builds you can open and use. No surprises.']],
                        ['name' => ['nl' => 'Lanceren', 'en' => 'Launch'], 'description' => ['nl' => 'Ik zet live, houd toezicht en blijf bereikbaar.', 'en' => 'I ship, monitor, and stay on call.']],
                    ],
                ]),
                $block('biography', [
                    'label' => ['nl' => 'Contact', 'en' => 'Contact'],
                    'body' => [
                        'nl' => '<p>Afspreken kan in Zwolle, Amsterdam of online. Stuur een bericht via de <a href="/nl/contact">contactpagina</a>.</p>',
                        'en' => '<p>We can meet in Zwolle, Amsterdam or online. Send a message through the <a href="/en/contact">contact page</a>.</p>',
                    ],
                ]),
            ],
        ];
    }
}
