<?php

use Illuminate\Support\Facades\Event;
use OursBlanc\Xms\Events\PagePublished;
use OursBlanc\Xms\Events\PageSaved;
use OursBlanc\Xms\Events\PageUnpublished;
use OursBlanc\Xms\Models\Page;
use OursBlanc\Xms\Models\PageRevision;

function makeBasicPage(array $overrides = []): Page
{
    return Page::create(array_merge([
        'locale' => 'fr',
        'slug' => 'accueil',
        'title' => 'Accueil',
        'blocks' => [
            ['uuid' => 'u1', 'type' => 'hero', 'data' => ['title' => 'Bienvenue', 'alignment' => 'left']],
        ],
        'seo' => ['title' => 'Accueil'],
        'status' => 'draft',
    ], $overrides));
}

it('automatically creates a translation group when none is given', function () {
    $page = makeBasicPage();

    expect($page->translation_group_id)->not->toBeNull();
});

it('reuses the given translation group when one is provided', function () {
    $first = makeBasicPage();
    $second = makeBasicPage(['locale' => 'en', 'translation_group_id' => $first->translation_group_id]);

    expect($second->translation_group_id)->toBe($first->translation_group_id);
});

it('creates a revision of the previous state before an update touches content', function () {
    $page = makeBasicPage();

    $page->update(['title' => 'Nouveau titre']);

    expect($page->revisions()->count())->toBe(1);

    /** @var PageRevision $revision */
    $revision = $page->revisions()->first();

    expect($revision->title)->toBe('Accueil')
        ->and($revision->author_type)->toBe('user');
});

it('does not create a revision when only unrelated fields change', function () {
    $page = makeBasicPage();

    $page->update(['updated_by' => 'someone']);

    expect($page->revisions()->count())->toBe(0);
});

it('prunes revisions beyond the configured retention limit', function () {
    config(['xms.revisions_per_page' => 2]);

    $page = makeBasicPage();

    $page->update(['title' => 'v2']);
    $page->update(['title' => 'v3']);
    $page->update(['title' => 'v4']);

    expect($page->revisions()->count())->toBe(2);
});

it('honors a custom author resolver', function () {
    Page::$authorResolver = fn () => ['type' => 'api_token', 'id' => '42'];

    $page = makeBasicPage();
    $page->update(['title' => 'v2']);

    expect($page->revisions()->first()->author_type)->toBe('api_token')
        ->and($page->revisions()->first()->author_id)->toBe('42');

    Page::$authorResolver = null;
});

it('dispatches PageSaved on every save and PagePublished when the status flips to published', function () {
    Event::fake([PageSaved::class, PagePublished::class, PageUnpublished::class]);

    $page = makeBasicPage();

    Event::assertDispatched(PageSaved::class);
    Event::assertNotDispatched(PagePublished::class);

    $page->update(['status' => 'published']);

    Event::assertDispatched(PagePublished::class);

    $page->update(['status' => 'draft']);

    Event::assertDispatched(PageUnpublished::class);
});

it('filters pages by a meta key/value pair', function () {
    makeBasicPage(['slug' => 'a', 'meta' => ['format' => 'video']]);
    makeBasicPage(['slug' => 'b', 'meta' => ['format' => 'display']]);

    $matches = Page::query()->whereMeta('format', 'video')->get();

    expect($matches)->toHaveCount(1)
        ->and($matches->first()->slug)->toBe('a');
});

it('falls back to title when list_title is empty', function () {
    $page = makeBasicPage(['list_title' => null]);

    expect($page->effectiveListTitle())->toBe('Accueil');

    $page->update(['list_title' => 'Card title']);

    expect($page->fresh()->effectiveListTitle())->toBe('Card title');
});

it('has no illustration url until one is attached', function () {
    $page = makeBasicPage();

    expect($page->illustrationUrl())->toBeNull();
});
