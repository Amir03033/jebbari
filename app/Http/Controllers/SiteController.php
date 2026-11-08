<?php

namespace App\Http\Controllers;

use App\Content\Renderer;
use App\Content\Site;
use Illuminate\Contracts\View\View;

/**
 * Public pages. Each action loads only live, language-complete content and 404s
 * for anything else (drafts, unpublished pages, records missing a translation).
 */
class SiteController extends Controller
{
    public function page(string $key): View
    {
        return Renderer::page(Site::page($key) ?? abort(404));
    }

    public function legal(string $slug): View
    {
        return Renderer::page(Site::legalPage($slug) ?? abort(404));
    }

    public function project(string $slug): View
    {
        return Renderer::project(Site::project($slug) ?? abort(404));
    }

    public function service(string $slug): View
    {
        return Renderer::service(Site::service($slug) ?? abort(404));
    }

    public function post(string $slug): View
    {
        return Renderer::post(Site::post($slug) ?? abort(404));
    }

    public function category(string $slug): View
    {
        return Renderer::category(Site::category($slug) ?? abort(404));
    }
}
