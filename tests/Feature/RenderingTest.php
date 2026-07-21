<?php

use Illuminate\Support\Facades\URL;
use OursBlanc\Xms\Models\Page;

beforeEach(function () {
    config([
        'xms.locales' => ['fr', 'en'],
        'xms.default_locale' => 'fr',
        'xms.locale_in_url' => true,
        'xms.default_locale_hidden' => true,
    ]);
});

function makeRenderablePage(array $overrides = []): Page
{
    return Page::create(array_merge([
        'locale' => 'fr',
        'slug' => 'accueil',
        'title' => 'Accueil',
        'blocks' => [
            ['uuid' => 'u1', 'type' => 'heading', 'data' => ['level' => 'h1', 'text' => 'Bienvenue']],
        ],
        'seo' => ['title' => 'Accueil | OursBlanc'],
        'status' => 'published',
        'published_at' => now(),
    ], $overrides));
}

it('renders a published page on the default (hidden) locale route', function () {
    makeRenderablePage();

    $this->get('/accueil')
        ->assertOk()
        ->assertSee('Bienvenue')
        ->assertSee('Accueil | OursBlanc');
});

it('renders a published page on a locale-prefixed route', function () {
    makeRenderablePage([
        'locale' => 'en',
        'slug' => 'home',
        'blocks' => [
            ['uuid' => 'u1', 'type' => 'heading', 'data' => ['level' => 'h1', 'text' => 'Welcome']],
        ],
    ]);

    $this->get('/en/home')
        ->assertOk()
        ->assertSee('Welcome');
});

it('returns a 404 for a draft page requested via the public route', function () {
    makeRenderablePage(['status' => 'draft', 'published_at' => null]);

    $this->get('/accueil')->assertNotFound();
});

it('returns a 404 for an unknown slug', function () {
    $this->get('/does-not-exist')->assertNotFound();
});

it('serves the sitemap with hreflang alternates across a translation group', function () {
    $fr = makeRenderablePage();
    makeRenderablePage([
        'locale' => 'en',
        'slug' => 'home',
        'translation_group_id' => $fr->translation_group_id,
    ]);

    $response = $this->get('/sitemap.xml')->assertOk();

    $response->assertSee(url('/accueil'), false)
        ->assertSee(url('/en/home'), false)
        ->assertSee('hreflang="en"', false)
        ->assertSee('hreflang="fr"', false);

    expect($response->headers->get('Content-Type'))->toContain('application/xml');
});

it('lets a validly signed preview URL render a draft page with no-store caching', function () {
    $page = makeRenderablePage(['status' => 'draft', 'published_at' => null]);

    $url = URL::temporarySignedRoute('xms.preview', now()->addMinutes(30), ['page' => $page->id]);

    $response = $this->get($url)->assertOk()->assertSee('Bienvenue');

    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('rejects a preview request without a valid signature', function () {
    $page = makeRenderablePage(['status' => 'draft', 'published_at' => null]);

    $this->get("/xms/preview/{$page->id}")->assertForbidden();
});
