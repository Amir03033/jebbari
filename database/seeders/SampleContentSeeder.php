<?php

namespace Database\Seeders;

use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Project;
use App\Models\Service;
use App\Support\Settings;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Local review content so the design can be judged with realistic structure:
 * `php artisan db:seed --class=SampleContentSeeder` (after CmsSeeder).
 *
 * Everything here is obviously sample material: titles start with "Voorbeeld" or
 * "Sample", images carry a "SAMPLE" label, and the email is on example.test.
 * It refuses to run in production. ServerScope is published with only the
 * facts from the brief (name and category).
 */
class SampleContentSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Sample content must never be seeded in production.');
        }

        $this->settings();
        $this->services();
        $this->projects();
        $this->posts();
        $this->pages();
    }

    private function settings(): void
    {
        Settings::save('contact', ['email' => 'hello@example.test']);
    }

    private function image(string $title, int $r, int $g, int $b): MediaAsset
    {
        $im = imagecreatetruecolor(1600, 1000);
        imagefill($im, 0, 0, imagecolorallocate($im, $r, $g, $b));
        $ink = imagecolorallocate($im, 14, 14, 15);
        $font = '/usr/share/fonts/truetype/liberation/LiberationMono-Regular.ttf';
        if (is_file($font)) {
            imagettftext($im, 28, 0, 48, 84, $ink, $font, 'SAMPLE IMAGE / VOORBEELDAFBEELDING');
        }
        $path = sys_get_temp_dir().'/'.str($title)->slug().'.jpg';
        imagejpeg($im, $path, 85);

        $asset = MediaAsset::create(['title' => $title, 'alt' => ['nl' => "Voorbeeldafbeelding: $title", 'en' => "Sample image: $title"]]);
        $asset->addMedia($path)->toMediaCollection('file');

        return $asset;
    }

    private function services(): void
    {
        foreach (Service::orderBy('sort')->get() as $i => $service) {
            if ($i === 1) {
                $service->update([
                    'benefits' => ['nl' => "Voorbeeld: een voordeel\nVoorbeeld: nog een voordeel", 'en' => "Sample: a benefit\nSample: another benefit"],
                    'included' => ['nl' => "Voorbeeld: ontwerp\nVoorbeeld: ontwikkeling\nVoorbeeld: oplevering", 'en' => "Sample: design\nSample: development\nSample: delivery"],
                    'timeline' => ['nl' => 'Voorbeeld: enkele weken', 'en' => 'Sample: a few weeks'],
                    'tags' => ['Laravel', 'Alpine'],
                    'description' => ['nl' => '<p>Voorbeeldtekst over deze dienst.</p>', 'en' => '<p>Sample text about this service.</p>'],
                    'packages' => [['name' => ['nl' => 'Voorbeeldpakket', 'en' => 'Sample package'], 'scope' => ['nl' => "Voorbeeld: inhoud een\nVoorbeeld: inhoud twee", 'en' => "Sample: item one\nSample: item two"], 'price_from_eur' => 2500, 'price_note' => ['nl' => 'Voorbeeldprijs', 'en' => 'Sample price'], 'visible' => true]],
                ]);
            }
            $service->publish();
        }
    }

    private function projects(): void
    {
        // ServerScope: only the facts from the brief.
        Project::where('slug_en', 'serverscope')->first()?->publish();

        $a = $this->image('Voorbeeldproject A', 214, 210, 200);
        $b = $this->image('Voorbeeldproject B', 190, 196, 204);

        foreach ([['A', $a], ['B', $b]] as $n => [$letter, $asset]) {
            $p = Project::create([
                'title' => ['nl' => "Voorbeeldproject $letter", 'en' => "Sample project $letter"],
                'kind' => ['nl' => 'Voorbeeldcategorie', 'en' => 'Sample category'],
                'summary' => ['nl' => 'Voorbeeld van een korte omschrijving voor de projectkaart.', 'en' => 'Sample of a short description for the project card.'],
                'brief' => ['nl' => 'Voorbeeld van de opdracht.', 'en' => 'Sample of the brief.'],
                'built' => ['nl' => '<p>Voorbeeld van wat er gebouwd is.</p>', 'en' => '<p>Sample of what was built.</p>'],
                'year' => 2026, 'stack' => ['Laravel', 'Alpine', 'Vite'], 'is_featured' => true, 'sort' => 20 + $n,
                'cover_media_id' => $asset->id,
                'gallery' => [['media_id' => $asset->id, 'caption' => ['nl' => 'Voorbeeldbijschrift', 'en' => 'Sample caption']]],
            ]);
            $p->publish();
        }
    }

    private function posts(): void
    {
        $category = PostCategory::create(['name' => ['nl' => 'Voorbeeldcategorie', 'en' => 'Sample category']]);
        $cover = $this->image('Voorbeeldartikel', 205, 208, 210);

        foreach ([1, 2] as $n) {
            Post::create([
                'title' => ['nl' => "Voorbeeldartikel $n", 'en' => "Sample article $n"],
                'excerpt' => ['nl' => 'Voorbeeld van een samenvatting.', 'en' => 'Sample of an excerpt.'],
                'body' => ['nl' => '<p>Voorbeeldtekst.</p><h2>Voorbeeldkop</h2><p>Meer voorbeeldtekst met een <a href="https://example.test">voorbeeldlink</a>.</p>', 'en' => '<p>Sample text.</p><h2>Sample heading</h2><p>More sample text with a <a href="https://example.test">sample link</a>.</p>'],
                'category_id' => $category->id, 'cover_media_id' => $cover->id, 'published_at' => now()->subDays($n),
            ])->publish();
        }
    }

    private function pages(): void
    {
        foreach (['home', 'work', 'services', 'blog', 'contact'] as $key) {
            $page = Page::where('key', $key)->first();

            if ($key === 'home') {
                $blocks = collect($page->blocks)->map(function ($b) {
                    if ($b['type'] === 'about_teaser') {
                        $b['data'] = ['visible' => true, 'label' => ['nl' => 'Over mij', 'en' => 'About'], 'heading' => ['nl' => 'Voorbeeld: een persoonlijke introductie', 'en' => 'Sample: a personal introduction'], 'body' => ['nl' => 'Voorbeeldtekst. De echte introductie schrijf je in de CMS.', 'en' => 'Sample text. You write the real introduction in the CMS.']];
                    }

                    return $b;
                })->all();
                $page->update(['blocks' => $blocks]);
            }

            $page->publish();
        }

        $about = Page::where('key', 'about')->first();
        $about->update(['blocks' => [
            ['type' => 'intro', 'data' => ['visible' => true, 'label' => ['nl' => 'Over mij', 'en' => 'About'], 'heading' => ['nl' => 'Voorbeeld: over mij', 'en' => 'Sample: about me'], 'body' => ['nl' => 'Voorbeeldtekst.', 'en' => 'Sample text.']]],
            ['type' => 'biography', 'data' => ['visible' => true, 'label' => ['nl' => 'Achtergrond', 'en' => 'Background'], 'body' => ['nl' => '<p>Voorbeeldtekst voor de biografie.</p>', 'en' => '<p>Sample text for the biography.</p>']]],
            ['type' => 'skills', 'data' => ['visible' => true, 'label' => ['nl' => 'Vaardigheden', 'en' => 'Skills'], 'items' => [['name' => ['nl' => 'Voorbeeldvaardigheid', 'en' => 'Sample skill'], 'description' => ['nl' => 'Voorbeeld.', 'en' => 'Sample.']]]]],
        ]]);
        $about->publish();
    }
}
