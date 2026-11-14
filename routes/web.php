<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StyleController;
use App\Http\Middleware\SetLocale;
use App\Support\Locale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// "/" picks a language from Accept-Language and defaults to Dutch.
Route::get('/', function (Request $request) {
    $locale = Locale::preferred($request);

    return redirect()->route($locale.'.home')->header('Vary', 'Accept-Language');
})->name('root');

// The Dutch About page moved from /nl/over-ons to /nl/over-mij.
Route::permanentRedirect('/nl/over-ons', '/nl/over-mij');

Route::get('/sitemap.xml', [SitemapController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');
Route::get('/llms.txt', [SitemapController::class, 'llms'])->name('llms');

foreach (Locale::all() as $locale) {
    Route::prefix($locale)->middleware(SetLocale::class.':'.$locale)->group(function () use ($locale) {
        // System pages (content comes from the CMS).
        foreach (Locale::PAGES as $page => $slugs) {
            Route::get($slugs[$locale], [SiteController::class, 'page'])
                ->defaults('key', $page)
                ->name($locale.'.'.$page);
        }

        // Contact form: validated, honeypot + minimum time, rate limited.
        Route::post(Locale::slug('contact', $locale), [ContactController::class, 'store'])->middleware('throttle:contact')->name($locale.'.contact.send');

        // Details with translated slugs.
        Route::get(Locale::slug('work', $locale).'/{slug}', [SiteController::class, 'project'])->name($locale.'.work.show');
        Route::get(Locale::slug('services', $locale).'/{slug}', [SiteController::class, 'service'])->name($locale.'.services.show');
        Route::get(Locale::slug('blog', $locale).'/'.Locale::CATEGORY_SEGMENT[$locale].'/{slug}', [SiteController::class, 'category'])->name($locale.'.blog.category');
        Route::get(Locale::slug('blog', $locale).'/{slug}', [SiteController::class, 'post'])->name($locale.'.blog.show');

        // Legal pages (privacy, terms) live at the slug the owner gives them. Must stay last.
        Route::get('{slug}', [SiteController::class, 'legal'])->where('slug', '[a-z0-9\-]+')->name($locale.'.legal');
    });
}

// Design-token test page. Not part of the public site.
Route::get('/_style', StyleController::class)
    ->middleware(SetLocale::class.':en')
    ->name('style');

// Draft previews: signed, expiring link plus a signed-in admin. See PreviewController.
Route::get('/preview/{type}/{id}', PreviewController::class)
    ->middleware(['signed', 'auth'])
    ->whereNumber('id')
    ->name('preview');
