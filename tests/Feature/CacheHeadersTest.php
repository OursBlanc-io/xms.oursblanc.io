<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use OursBlanc\Xms\Http\Middleware\SetCacheHeaders;
use OursBlanc\Xms\Models\Page;

beforeEach(function () {
    config([
        'xms.locales' => ['fr', 'en'],
        'xms.default_locale' => 'fr',
        'xms.locale_in_url' => true,
        'xms.default_locale_hidden' => true,
        'xms.cache.s_maxage' => 3600,
        'xms.cache.max_age' => 0,
    ]);

    Page::create([
        'locale' => 'fr', 'slug' => 'accueil', 'title' => 'X',
        'blocks' => [], 'seo' => [], 'status' => 'published', 'published_at' => now(),
    ]);
});

it('sets a public, cacheable Cache-Control header for an anonymous visitor', function () {
    $response = $this->get('/accueil')->assertOk();

    // Symfony's ResponseHeaderBag recomposes Cache-Control from parsed
    // directives (its own canonical order), so directives are asserted
    // individually rather than as one literal string.
    expect($response->headers->get('Cache-Control'))
        ->toContain('public')
        ->toContain('s-maxage=3600')
        ->toContain('max-age=0');
});

it('forces no-store when a request is authenticated', function () {
    $middleware = new SetCacheHeaders;

    Auth::shouldReceive('check')->andReturn(true);

    $response = $middleware->handle(
        Request::create('/accueil'),
        fn () => response('ok', 200),
    );

    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('forces no-store on a 404', function () {
    $response = $this->get('/does-not-exist');

    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('the preview route always sends no-store regardless of the render middleware', function () {
    $page = Page::first();

    $url = URL::temporarySignedRoute('xms.preview', now()->addMinutes(30), ['page' => $page->id]);

    $response = $this->get($url)->assertOk();

    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});
