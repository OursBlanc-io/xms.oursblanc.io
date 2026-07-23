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

        if ($hideDefaultLocale) {
            Route::get('/{slug?}', RenderController::class)
                ->middleware(SetCacheHeaders::class)
                ->where('slug', '.*')
                ->defaults('locale', $defaultLocale)
                ->name('xms.render');
        }
    } else {
        Route::get('/{slug?}', RenderController::class)
            ->middleware(SetCacheHeaders::class)
            ->where('slug', '.*')
            ->defaults('locale', $defaultLocale)
            ->name('xms.render');
    }
});
