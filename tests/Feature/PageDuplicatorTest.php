<?php

use Illuminate\Support\Facades\Storage;
use OursBlanc\Xms\Media\PageMediaSynchronizer;
use OursBlanc\Xms\Models\Category;
use OursBlanc\Xms\Models\Page;
use OursBlanc\Xms\Support\PageDuplicator;

beforeEach(function () {
    Storage::fake('public');
});

function pageWithImageBlock(array $overrides = []): Page
{
    Storage::disk('public')->put('xms-pending/test.png', file_get_contents(__DIR__.'/../Fixtures/test-image.png'));

    $page = Page::create(array_merge([
        'locale' => 'fr',
        'slug' => 'original',
        'title' => 'Original',
        'blocks' => [
            ['uuid' => 'u1', 'type' => 'image', 'data' => ['image' => 'xms-pending/test.png', 'alt' => 'Alt text', 'width' => 'content']],
        ],
        'seo' => [],
        'status' => 'published',
    ], $overrides));

    app(PageMediaSynchronizer::class)->sync($page);

    return $page->fresh();
}

it('creates an independent draft copy with its own slug, translation group, and title', function () {
    $page = pageWithImageBlock();

    $duplicate = app(PageDuplicator::class)->duplicate($page);

    expect($duplicate->id)->not->toBe($page->id)
        ->and($duplicate->slug)->toBe('original-copy')
        ->and($duplicate->title)->toBe('Original (copy)')
        ->and($duplicate->status)->toBe('draft')
        ->and($duplicate->translation_group_id)->not->toBe($page->translation_group_id)
        ->and($duplicate->locale)->toBe($page->locale);
});

it('regenerates block uuids and copies media into the new blocks with remapped ids', function () {
    $page = pageWithImageBlock();
    $originalMediaId = $page->blocks[0]['data']['image'];

    $duplicate = app(PageDuplicator::class)->duplicate($page);

    expect($duplicate->blocks[0]['uuid'])->not->toBe('u1');

    $newMediaId = $duplicate->blocks[0]['data']['image'];

    expect($newMediaId)->not->toBe($originalMediaId);

    $copiedMedia = $duplicate->media()->first();

    expect($copiedMedia->id)->toBe($newMediaId)
        ->and($copiedMedia->collection_name)->toBe("block-{$duplicate->blocks[0]['uuid']}")
        ->and($copiedMedia->model_id)->toBe($duplicate->id);

    // The original page's own media is untouched.
    expect($page->fresh()->media()->count())->toBe(1);
});

it('copies categories to the duplicate', function () {
    $category = Category::create(['name' => 'Blog', 'slug' => 'blog']);
    $page = pageWithImageBlock();
    $page->categories()->sync([$category->id]);

    $duplicate = app(PageDuplicator::class)->duplicate($page->fresh());

    expect($duplicate->categories()->pluck('xms_categories.id')->all())->toBe([$category->id]);
});

it('copies the illustration', function () {
    $page = pageWithImageBlock();
    Storage::disk('public')->put('xms-pending/cover.png', file_get_contents(__DIR__.'/../Fixtures/test-image.png'));
    $page->addMediaFromDisk('xms-pending/cover.png', 'public')->toMediaCollection(Page::ILLUSTRATION_COLLECTION, 'public');

    $duplicate = app(PageDuplicator::class)->duplicate($page->fresh());

    expect($duplicate->illustrationUrl())->not->toBeNull()
        ->and($duplicate->illustrationUrl())->not->toBe($page->fresh()->illustrationUrl());
});

it('does not copy orphaned (pending-deletion) media', function () {
    $page = pageWithImageBlock();

    // Removing the block marks its media orphaned (pending_deletion_at set)
    // without deleting it outright (a 24h grace period).
    $page->update(['blocks' => []]);
    app(PageMediaSynchronizer::class)->sync($page->fresh());

    $duplicate = app(PageDuplicator::class)->duplicate($page->fresh());

    expect($duplicate->media()->count())->toBe(0);
});

it('generates a unique slug across duplicates, and handles the empty (homepage) slug', function () {
    $page = pageWithImageBlock(['slug' => '']);

    $first = app(PageDuplicator::class)->duplicate($page);
    $second = app(PageDuplicator::class)->duplicate($page->fresh());

    expect($first->slug)->toBe('copy')
        ->and($second->slug)->toBe('copy-2');
});
