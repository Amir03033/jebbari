<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\PreviewController;
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

foreach (Locale::all() as $locale) {
    Route::prefix($locale)->middleware(SetLocale::class.':'.$locale)->group(function () use ($locale) {
        foreach (Locale::PAGES as $page => $slugs) {
            Route::get($slugs[$locale], PageController::class)
                ->defaults('page', $page)
                ->name($locale.'.'.$page);
        }
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
