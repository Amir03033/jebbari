<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\MediaAssets\Pages\CreateMediaAsset;
use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Models\Service;
use App\Support\MediaSizes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileUnacceptableForCollection;
use Tests\Support\AdminTestCase;

class MediaTest extends AdminTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        // Tests run inside a transaction, so queue conversions straight away.
        config(['media-library.queue_conversions_after_database_commit' => false]);
    }

    /** A real JPEG with a comment segment and an EXIF block carrying a fake GPS marker. */
    private function jpegWithMetadata(int $w = 1600, int $h = 1000): string
    {
        $im = imagecreatetruecolor($w, $h);
        imagefill($im, 0, 0, imagecolorallocate($im, 30, 90, 160));
        ob_start();
        imagejpeg($im, null, 90);
        $jpeg = ob_get_clean();

        $comment = "\xFF\xFE".pack('n', 2 + strlen('GPS-MARKER-52.5N-6.1E')).'GPS-MARKER-52.5N-6.1E';
        $exifBody = "Exif\0\0GPS-EXIF-MARKER-52.5N";
        $exif = "\xFF\xE1".pack('n', 2 + strlen($exifBody)).$exifBody;

        return substr($jpeg, 0, 2).$exif.$comment.substr($jpeg, 2);
    }

    private function upload(string $bytes, string $name = 'foto.jpg'): MediaAsset
    {
        $path = sys_get_temp_dir().'/'.uniqid('up_').'-'.$name;
        file_put_contents($path, $bytes);

        $asset = MediaAsset::create(['title' => 'Test']);
        $asset->addMedia($path)->toMediaCollection('file');

        return $asset->fresh();
    }

    public function test_uploads_are_converted_at_every_width_in_every_supported_format(): void
    {
        $asset = $this->upload($this->jpegWithMetadata(2600, 1600));
        $media = $asset->file();

        $this->assertTrue($media->hasGeneratedConversion('thumb'));

        foreach (MediaSizes::WIDTHS as $width) {
            foreach (MediaSizes::formats() as $format) {
                $name = "{$format}-{$width}";
                $this->assertTrue($media->hasGeneratedConversion($name), "missing $name");
                $path = $media->getPath($name);
                $this->assertFileExists($path);
                $this->assertSame("image/{$format}", mime_content_type($path), $name);
                $this->assertSame($width, getimagesize($path)[0] ?? null, "$name width");
            }
        }
    }

    public function test_avif_is_only_used_where_the_server_can_really_encode_it(): void
    {
        $asset = $this->upload($this->jpegWithMetadata());
        $hasAvif = $asset->file()->hasGeneratedConversion('avif-768');

        $this->assertSame(MediaSizes::avifSupported(), $hasAvif);
        $this->assertTrue($asset->file()->hasGeneratedConversion('webp-768'), 'WebP is always produced');
        $this->assertSame(MediaSizes::avifSupported(), in_array('image/avif', MediaSizes::mimes(), true));
    }

    public function test_camera_metadata_is_removed_from_the_stored_original(): void
    {
        $bytes = $this->jpegWithMetadata();
        $this->assertStringContainsString('GPS-EXIF-MARKER', $bytes, 'fixture sanity');

        $asset = $this->upload($bytes);
        $stored = file_get_contents($asset->file()->getPath());

        $this->assertStringNotContainsString('GPS-EXIF-MARKER', $stored);
        $this->assertStringNotContainsString('GPS-MARKER', $stored);
        $this->assertStringNotContainsString('Exif', $stored);
        $this->assertSame([1600, 1000], array_slice(getimagesizefromstring($stored), 0, 2), 'pixels are preserved');
    }

    public function test_pixel_size_is_recorded(): void
    {
        $asset = $this->upload($this->jpegWithMetadata(1200, 800));

        $this->assertSame(1200, $asset->file()->getCustomProperty('width'));
        $this->assertSame(800, $asset->file()->getCustomProperty('height'));
    }

    public function test_svg_and_other_non_image_types_are_refused(): void
    {
        $svg = sys_get_temp_dir().'/x.svg';
        file_put_contents($svg, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        $this->expectException(FileUnacceptableForCollection::class);
        MediaAsset::create(['title' => 'Svg'])->addMedia($svg)->toMediaCollection('file');
    }

    public function test_pdf_files_are_refused(): void
    {
        $pdf = sys_get_temp_dir().'/x.pdf';
        file_put_contents($pdf, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF");

        $this->expectException(FileUnacceptableForCollection::class);
        MediaAsset::create(['title' => 'Pdf'])->addMedia($pdf)->toMediaCollection('file');
    }

    public function test_the_upload_form_enforces_type_size_and_dimensions(): void
    {
        $this->actingAs($this->owner());

        Livewire::test(CreateMediaAsset::class)
            ->fillForm(['title' => 'Svg', 'file' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml')])
            ->call('create')->assertHasFormErrors(['file']);

        Livewire::test(CreateMediaAsset::class)
            ->fillForm(['title' => 'Groot', 'file' => UploadedFile::fake()->image('groot.jpg', 1200, 800)->size(MediaSizes::MAX_KB + 500)])
            ->call('create')->assertHasFormErrors(['file']);

        Livewire::test(CreateMediaAsset::class)
            ->fillForm(['title' => 'Klein', 'file' => UploadedFile::fake()->image('klein.jpg', 200, 100)])
            ->call('create')->assertHasFormErrors(['file']);
    }

    public function test_a_valid_upload_through_the_form_creates_an_asset_with_conversions(): void
    {
        $this->actingAs($this->owner());

        Livewire::test(CreateMediaAsset::class)
            ->fillForm(['title' => 'Kapper', 'file' => UploadedFile::fake()->image('kapper.jpg', 1600, 1000), 'alt' => ['nl' => 'Een kapper', 'en' => 'A barber']])
            ->call('create')->assertHasNoFormErrors();

        $asset = MediaAsset::where('title', 'Kapper')->firstOrFail();
        $this->assertNotNull($asset->file());
        $this->assertTrue($asset->file()->hasGeneratedConversion('webp-768'));
        $this->assertSame([], $asset->missingAlt());
    }

    public function test_alt_text_is_checked_per_language_unless_decorative(): void
    {
        $asset = MediaAsset::create(['title' => 'A', 'alt' => ['nl' => 'Beschrijving']]);
        $this->assertSame(['EN'], $asset->missingAlt());

        $asset->update(['alt' => null]);
        $this->assertSame(['NL', 'EN'], $asset->missingAlt());

        $asset->update(['is_decorative' => true]);
        $this->assertSame([], $asset->missingAlt());
    }

    public function test_an_asset_in_use_cannot_be_deleted(): void
    {
        $owner = $this->owner();
        $asset = MediaAsset::create(['title' => 'Gebruikt']);

        $this->assertTrue($owner->can('delete', $asset), 'unused assets can be deleted');

        Service::create(['title' => ['nl' => 'S', 'en' => 'S'], 'image_id' => $asset->id]);

        $this->assertFalse($owner->can('delete', $asset->fresh()));
        $this->assertContains('Service: S', $asset->usages());
    }

    public function test_usage_is_detected_in_galleries_blocks_posts_and_unpublished_drafts(): void
    {
        $owner = $this->owner();

        $inGallery = MediaAsset::create(['title' => 'G']);
        Project::create(['title' => ['nl' => 'P', 'en' => 'P'], 'gallery' => [['media_id' => $inGallery->id, 'caption' => ['nl' => 'a', 'en' => 'b']]]]);

        $inBlock = MediaAsset::create(['title' => 'B']);
        Page::create(['key' => 'about', 'title' => ['nl' => 'O', 'en' => 'A'], 'blocks' => [['type' => 'image', 'data' => ['media_id' => (string) $inBlock->id]]]]);

        $inCover = MediaAsset::create(['title' => 'C']);
        Post::create(['title' => ['nl' => 'T', 'en' => 'T'], 'cover_media_id' => $inCover->id]);

        $inDraft = MediaAsset::create(['title' => 'D']);
        $project = Project::create(['title' => ['nl' => 'Live', 'en' => 'Live'], 'kind' => ['nl' => 'k', 'en' => 'k'], 'brief' => ['nl' => 'b', 'en' => 'b'], 'outcome' => ['nl' => 'o', 'en' => 'o']]);
        $project->publish();
        $project->fresh()->saveFromForm(['cover_media_id' => $inDraft->id]);

        foreach ([$inGallery, $inBlock, $inCover, $inDraft] as $asset) {
            $this->assertNotEmpty($asset->usages(), "{$asset->title} should be reported as used");
            $this->assertFalse($owner->can('delete', $asset->fresh()), "{$asset->title} must be protected");
        }

        $this->assertStringContainsString('(concept)', implode(' ', $inDraft->usages()));
    }

    public function test_editors_can_upload_but_not_delete(): void
    {
        $editor = $this->editor();
        $asset = MediaAsset::create(['title' => 'X']);

        $this->assertTrue($editor->can('create', MediaAsset::class));
        $this->assertTrue($editor->can('update', $asset));
        $this->assertFalse($editor->can('delete', $asset));
    }

    public function test_support_users_have_no_media_access(): void
    {
        $this->assertFalse($this->support()->can('viewAny', MediaAsset::class));
    }
}
