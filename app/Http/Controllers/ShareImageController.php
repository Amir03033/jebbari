<?php

namespace App\Http\Controllers;

use App\Content\Site;
use App\Support\Seo;
use App\Support\ShareImage;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * A share image (Open Graph) per project and article: the title set on the
 * brand surface. Generated once and kept on disk; when the server cannot draw
 * images the default share image is used instead.
 */
class ShareImageController extends Controller
{
    public function __invoke(string $locale, string $type, string $slug): BinaryFileResponse|RedirectResponse
    {
        abort_unless(in_array($locale, config('site.locales'), true), 404);
        app()->setLocale($locale);

        $record = match ($type) {
            'work' => Site::project($slug, $locale),
            'blog' => Site::post($slug, $locale),
            default => null,
        } ?? abort(404);

        $label = $type === 'work' ? (loc($record->kind) ?: __('site.nav.work')) : __('site.nav.blog');
        $path = ShareImage::for($type.'-'.$record->getKey().'-'.$locale, (string) loc($record->title), (string) $label);

        return $path
            ? response()->file($path, ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=86400'])
            : redirect(asset(Seo::DEFAULT_IMAGE));
    }
}
