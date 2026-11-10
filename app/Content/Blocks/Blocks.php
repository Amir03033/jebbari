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
        $list = match ($pageKey) {
            'home' => [new HeroBlock, new ServicesOverviewBlock, new WorkSelectionBlock, new ProcessBlock, new AboutTeaserBlock, new StatsBlock, new AutoBlock('tech_marquee', 'Technologieën (automatisch)'), new SpecSheetBlock, new TestimonialsBlock, new ClosingCtaBlock],
            'about' => [
                new IntroBlock,
                new RichTextBlock('biography', 'Biografie'),
                new ImageBlock,
                new ListBlock('skills', 'Vaardigheden', true),
                new ExperienceBlock,
                new ListBlock('principles', 'Principes', false, 3, 4),
                new LineBlock('meeting_line', 'Afspreeklijn'),
            ],
            'services' => [new IntroBlock, new AutoBlock('service_list', 'Dienstenlijst (automatisch)'), new AutoBlock('packages_table', 'Pakketten en prijzen (automatisch)')],
            'work' => [new IntroBlock],
            'blog' => [new IntroBlock],
            'contact' => [new IntroBlock, new LineBlock('meeting_line', 'Afspreeklijn'), new LineBlock('reply_promise', 'Belofte reactietijd')],
            default => [new RichTextBlock('rich_text', 'Tekst')],
        };

        return collect($list)->keyBy(fn (Block $b) => $b->type())->all();
    }

    public static function find(string $pageKey, string $type): ?Block
    {
        return self::allowedFor($pageKey)[$type] ?? null;
    }
}
