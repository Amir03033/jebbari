<?php

namespace App\Http\Controllers;

use App\Content\Renderer;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Preview of a draft or of unpublished changes. Needs a signed, expiring link
 * AND a signed-in admin who may view the record. Never indexed or cached.
 */
class PreviewController extends Controller
{
    private const TYPES = [
        'page' => Page::class,
        'service' => Service::class,
        'project' => Project::class,
        'testimonial' => Testimonial::class,
        'post' => Post::class,
    ];

    public function __invoke(Request $request, string $type, int $id): View
    {
        $model = self::TYPES[$type] ?? abort(404);
        $locale = in_array($request->query('lang'), ['nl', 'en'], true) ? $request->query('lang') : 'nl';

        $query = $model::query();
        $record = method_exists($model, 'bootSoftDeletes') ? $query->withTrashed()->findOrFail($id) : $query->findOrFail($id);

        Gate::authorize('view', $record);
        app()->setLocale($locale);

        $other = $locale === 'nl' ? 'en' : 'nl';
        $draft = $record->withDraft();
        $preview = [
            'state' => $record->workflowState(),
            'switchUrl' => $record->previewUrl($other),
            'other' => $other,
        ];

        // The real page, with the unpublished changes applied.
        return match ($type) {
            'page' => Renderer::page($draft, $preview),
            'service' => Renderer::service($draft, $preview),
            'project' => Renderer::project($draft, $preview),
            'post' => Renderer::post($draft, $preview),
            // Testimonials have no page of their own: show their content.
            default => view('pages.preview', [
                'record' => $draft,
                'state' => $preview['state'],
                'locale' => $locale,
                'switchUrl' => $preview['switchUrl'],
                'other' => $other,
                'type' => $type,
            ]),
        };
    }
}
