<?php

namespace App\Content\Blocks;

/**
 * The fixed set of block types each page may use. Adding a layout means adding a
 * block class and its Blade view; editors cannot create new layouts.
 */
class Blocks
{
    /** @return array<string, Block> type => block, in the order they are offered */
    public static function allowedFor(string $pageKey): array
    {
        $github = new AutoBlock('github', 'GitHub-projecten (automatisch)');
        $newsletter = new AutoBlock('newsletter', 'Nieuwsbrief-aanmelding');

        $list = match ($pageKey) {
            'home' => [new HeroBlock, new ServicesOverviewBlock, new WorkSelectionBlock, new ProcessBlock, new AboutTeaserBlock, new StatsBlock, new AutoBlock('tech_marquee', 'Technologieën (automatisch)'), new SpecSheetBlock, new TestimonialsBlock, $github, new FaqBlock, $newsletter, new ClosingCtaBlock],
            'about' => [
                new IntroBlock,
                new RichTextBlock('biography', 'Biografie'),
                new ImageBlock,
                new ListBlock('skills', 'Vaardigheden', true),
                new ExperienceBlock,
                new ListBlock('principles', 'Principes', false, 3, 6),
                $github,
                new LineBlock('meeting_line', 'Afspreeklijn'),
            ],
            'services' => [new IntroBlock, new AutoBlock('service_list', 'Dienstenlijst (automatisch)'), new AutoBlock('packages_table', 'Pakketten en prijzen (automatisch)'), new FaqBlock],
            'work' => [new IntroBlock],
            'blog' => [new IntroBlock, $newsletter],
            'contact' => [new IntroBlock, new LineBlock('meeting_line', 'Afspreeklijn'), new LineBlock('reply_promise', 'Belofte reactietijd'), new FaqBlock],
            // What I am working on right now.
            'now' => [new IntroBlock, new ListBlock('focus', 'Nu mee bezig', true), new RichTextBlock('rich_text', 'Tekst'), $github],
            // The tools I work with: one list per category, as many as needed.
            'uses' => [new IntroBlock, new ListBlock('gear', 'Categorie met onderdelen', true, 1, 20, false)],
            // The CV is assembled from the About page; only its opening is edited here.
            'cv' => [new IntroBlock],
            default => [new RichTextBlock('rich_text', 'Tekst')],
        };

        return collect($list)->keyBy(fn (Block $b) => $b->type())->all();
    }

    public static function find(string $pageKey, string $type): ?Block
    {
        return self::allowedFor($pageKey)[$type] ?? null;
    }
}
