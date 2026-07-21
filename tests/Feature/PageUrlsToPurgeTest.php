<?php

use Illuminate\Support\Facades\Event;
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

it('includes the page own url and the sitemap', function () {
    $page = Page::create([
        'locale' => 'fr', 'slug' => 'accueil', 'title' => 'X',
        'blocks' => [], 'seo' => [], 'status' => 'published', 'published_at' => now(),
    ]);

    $urls = $page->urlsToPurge();

    expect($urls)->toContain(PageUrlGenerator::for($page))
        ->toContain(url('/sitemap.xml'));
});

it('includes published sibling locales but not draft ones', function () {
    $fr = Page::create([
        'locale' => 'fr', 'slug' => 'accueil', 'title' => 'X',
        'blocks' => [], 'seo' => [], 'status' => 'published', 'published_at' => now(),
    ]);

    $en = Page::create([
        'locale' => 'en', 'slug' => 'home', 'title' => 'X', 'translation_group_id' => $fr->translation_group_id,
        'blocks' => [], 'seo' => [], 'status' => 'published', 'published_at' => now(),
    ]);

    $de = Page::create([
        'locale' => 'de', 'slug' => 'start', 'title' => 'X', 'translation_group_id' => $fr->translation_group_id,
        'blocks' => [], 'seo' => [], 'status' => 'draft',
    ]);

    $urls = $fr->fresh()->urlsToPurge();

    expect($urls)->toContain(PageUrlGenerator::for($en))
        ->not->toContain(PageUrlGenerator::for($de));
});

it('lets host apps append extra urls via the xms.purge_urls event', function () {
    Event::listen('xms.purge_urls', fn (Page $page) => ['https://oursblanc.test/listing']);

    $page = Page::create([
        'locale' => 'fr', 'slug' => 'accueil', 'title' => 'X',
        'blocks' => [], 'seo' => [], 'status' => 'published', 'published_at' => now(),
    ]);

    expect($page->urlsToPurge())->toContain('https://oursblanc.test/listing');
});
