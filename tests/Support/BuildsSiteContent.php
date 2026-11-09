<?php

namespace Tests\Support;

use App\Models\MediaAsset;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Support\Facades\Storage;

trait BuildsSiteContent
{
    protected function makeService(array $o = [], bool $publish = true): Service
    {
        $service = Service::create($o + [
            'title' => ['nl' => 'Webbouw', 'en' => 'Web building'],
            'summary' => ['nl' => 'Wij bouwen webbouw.', 'en' => 'We build web building.'],
        ]);

        return $publish ? tap($service)->publish() : $service;
    }

    protected function makeProject(array $o = [], bool $publish = true): Project
    {
        $project = Project::create($o + [
            'title' => ['nl' => 'Testproject', 'en' => 'Test project'],
            'kind' => ['nl' => 'Webapplicatie', 'en' => 'Web application'],
            'is_featured' => true,
        ]);

        return $publish ? tap($project)->publish() : $project;
    }

    protected function makePost(array $o = [], bool $publish = true): Post
    {
        $post = Post::create($o + [
            'title' => ['nl' => 'Een artikel', 'en' => 'An article'],
            'excerpt' => ['nl' => 'Korte samenvatting.', 'en' => 'Short excerpt.'],
            'body' => ['nl' => '<p>Nederlandse tekst.</p>', 'en' => '<p>English text.</p>'],
        ]);

        return $publish ? tap($post)->publish() : $post;
    }

    protected function makeCategory(array $o = []): PostCategory
    {
        return PostCategory::create($o + ['name' => ['nl' => 'Nieuws', 'en' => 'News']]);
    }

    /** A real uploaded image with conversions, in the fake public disk. */
    protected function makeImage(string $title = 'Foto', int $w = 1600, int $h = 1000, ?array $alt = ['nl' => 'Een foto', 'en' => 'A photo']): MediaAsset
    {
        Storage::fake('public');
        config(['media-library.queue_conversions_after_database_commit' => false]);

        $im = imagecreatetruecolor($w, $h);
        imagefill($im, 0, 0, imagecolorallocate($im, 120, 130, 140));
        $path = sys_get_temp_dir().'/'.uniqid('img_').'.jpg';
        imagejpeg($im, $path, 85);

        $asset = MediaAsset::create(['title' => $title, 'alt' => $alt]);
        $asset->addMedia($path)->toMediaCollection('file');

        return $asset->fresh();
    }
}
