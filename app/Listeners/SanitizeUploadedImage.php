<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use Throwable;

/**
 * Re-encodes an uploaded image so camera metadata (EXIF, including GPS
 * location) is removed from the stored original, applies the camera's
 * orientation first, and records the pixel size. Runs before conversions.
 */
class SanitizeUploadedImage
{
    public function handle(MediaHasBeenAddedEvent $event): void
    {
        $media = $event->media;

        if (! str_starts_with((string) $media->mime_type, 'image/')) {
            return;
        }

        try {
            $disk = Storage::disk($media->disk);
            $path = $media->getPathRelativeToRoot();
            $bytes = $disk->get($path);
            $image = @imagecreatefromstring($bytes);

            if (! $image) {
                return;
            }

            if ($media->mime_type === 'image/jpeg' && function_exists('exif_read_data')) {
                $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($bytes));
                $rotated = match ($exif['Orientation'] ?? 1) {
                    3 => imagerotate($image, 180, 0),
                    6 => imagerotate($image, -90, 0),
                    8 => imagerotate($image, 90, 0),
                    default => $image,
                };
                $image = $rotated ?: $image;
            }

            imagesavealpha($image, true);

            ob_start();
            match ($media->mime_type) {
                'image/jpeg' => imagejpeg($image, null, 90),
                'image/png' => imagepng($image, null, 6),
                'image/webp' => imagewebp($image, null, 90),
                'image/avif' => imageavif($image, null, 80),
                default => null,
            };
            $clean = (string) ob_get_clean();

            if ($clean === '') {
                return;
            }

            $disk->put($path, $clean);

            $media->size = strlen($clean);
            $media->setCustomProperty('width', imagesx($image));
            $media->setCustomProperty('height', imagesy($image));
            $media->saveQuietly();
        } catch (Throwable $e) {
            Log::warning('Could not sanitise uploaded image: '.$e->getMessage());
        }
    }
}
