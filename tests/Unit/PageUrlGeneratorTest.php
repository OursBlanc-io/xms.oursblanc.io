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

it('omits the locale prefix for the default hidden locale', function () {
    $page = Page::create(['locale' => 'fr', 'slug' => 'produits/smartskin', 'title' => 'X', 'blocks' => [], 'seo' => []]);

    expect(PageUrlGenerator::for($page))->toBe(url('/produits/smartskin'));
});

it('prefixes non-default locales', function () {
    $page = Page::create(['locale' => 'en', 'slug' => 'products/smartskin', 'title' => 'X', 'blocks' => [], 'seo' => []]);

    expect(PageUrlGenerator::for($page))->toBe(url('/en/products/smartskin'));
});

it('prefixes every locale, including the default one, when hiding is disabled', function () {
    config(['xms.default_locale_hidden' => false]);

    $page = Page::create(['locale' => 'fr', 'slug' => 'accueil', 'title' => 'X', 'blocks' => [], 'seo' => []]);

    expect(PageUrlGenerator::for($page))->toBe(url('/fr/accueil'));
});

it('resolves the homepage (empty slug) to the root URL', function () {
    $page = Page::create(['locale' => 'fr', 'slug' => '', 'title' => 'X', 'blocks' => [], 'seo' => []]);

    expect(PageUrlGenerator::for($page))->toBe(url('/'));
});

it('ignores locale prefixing entirely when locale_in_url is disabled', function () {
    config(['xms.locale_in_url' => false]);

    $page = Page::create(['locale' => 'en', 'slug' => 'about', 'title' => 'X', 'blocks' => [], 'seo' => []]);

    expect(PageUrlGenerator::for($page))->toBe(url('/about'));
});
