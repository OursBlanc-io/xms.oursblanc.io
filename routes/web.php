<?php

use Illuminate\Support\Facades\Route;
use OursBlanc\Xms\Http\Controllers\FormSubmissionController;
use OursBlanc\Xms\Http\Controllers\PreviewController;
use OursBlanc\Xms\Http\Controllers\RenderController;
use OursBlanc\Xms\Http\Controllers\SitemapController;
use OursBlanc\Xms\Http\Middleware\SetCacheHeaders;

Route::middleware('web')->group(function () {
    Route::get('/sitemap.xml', SitemapController::class)->name('xms.sitemap');

    Route::get('/xms/preview/{page}', PreviewController::class)->name('xms.preview');

    Route::post('/xms/forms/{form:slug}/submit', FormSubmissionController::class)
        ->middleware('throttle:'.config('xms.forms.throttle', '10,1'))
        ->name('xms.forms.submit');

    $locales = config('xms.locales', []);
    $defaultLocale = config('xms.default_locale');
    $hideDefaultLocale = config('xms.default_locale_hidden');

    if (config('xms.locale_in_url') && $locales !== []) {
        $prefixedLocales = $hideDefaultLocale
            ? array_values(array_diff($locales, [$defaultLocale]))
            : $locales;

        if ($prefixedLocales !== []) {
            Route::get('/{locale}/{slug?}', RenderController::class)
                ->middleware(SetCacheHeaders::class)
                ->where('locale', implode('|', array_map('preg_quote', $prefixedLocales)))
                ->where('slug', '.*')
                ->name('xms.render.locale');
        }
    }

    // A *fallback* route, not a plain `Route::get('/{slug?}', ...)` — a
    // fallback is only ever tried once every other route in the app has
    // failed to match, regardless of registration/provider-boot order. A
    // plain wildcard GET route here previously shadowed unrelated routes
    // registered later (Laravel's own `/up`, laravel/mcp's `/mcp/xms`, ...),
    // since this package's service provider boots — and so registers this
    // route — before the app's own routes/web.php ever loads.
    //
    // Serves both a locale-agnostic page (Page::locale === null) and, when
    // there's only one effective locale (locale_in_url disabled, or the
    // default locale hidden from the URL), that locale's own pages —
    // RenderController tells the cases apart.
    Route::fallback(RenderController::class)
        ->middleware(SetCacheHeaders::class)
        ->name('xms.render');
});
