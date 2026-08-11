<?php

use OursBlanc\Xms\Models\Page;
use OursBlanc\Xms\Support\PageUrlGenerator;

beforeEach(function () {
    config([
        'xms.locales' => ['fr', 'en'],
        'xms.default_locale' => 'fr',
        'xms.locale_in_url' => true,
        'xms.default_locale_hidden' => true,
    ]);
});

it('lists published pages with their url', function () {
    $page = Page::create([
        'locale' => 'fr', 'slug' => 'accueil', 'title' => 'X',
        'blocks' => [], 'seo' => [], 'status' => 'published', 'published_at' => now(),
    ]);

    $response = $this->get('/sitemap.xml');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee(PageUrlGenerator::for($page), false);
});

it('excludes draft pages', function () {
    Page::create([
        'locale' => 'fr', 'slug' => 'brouillon', 'title' => 'X',
        'blocks' => [], 'seo' => [], 'status' => 'draft',
    ]);

    $response = $this->get('/sitemap.xml');

    $response->assertOk()->assertDontSee('brouillon');
});

it('excludes pages marked noindex', function () {
    $page = Page::create([
        'locale' => 'fr', 'slug' => 'mentions-legales', 'title' => 'X',
        'blocks' => [], 'seo' => ['robots' => 'noindex,follow'],
        'status' => 'published', 'published_at' => now(),
    ]);

    $response = $this->get('/sitemap.xml');

    $response->assertOk()->assertDontSee(PageUrlGenerator::for($page), false);
});

it('declares hreflang alternates for sibling locales', function () {
    $fr = Page::create([
        'locale' => 'fr', 'slug' => 'accueil', 'title' => 'X',
        'blocks' => [], 'seo' => [], 'status' => 'published', 'published_at' => now(),
    ]);

    $en = Page::create([
        'locale' => 'en', 'slug' => 'home', 'title' => 'X', 'translation_group_id' => $fr->translation_group_id,
        'blocks' => [], 'seo' => [], 'status' => 'published', 'published_at' => now(),
    ]);

    $response = $this->get('/sitemap.xml');

    $response->assertOk()
        ->assertSee('hreflang="fr" href="'.PageUrlGenerator::for($fr).'"', false)
        ->assertSee('hreflang="en" href="'.PageUrlGenerator::for($en).'"', false);
});
