<?php

use Illuminate\Support\Facades\Storage;
use OursBlanc\Xms\Media\PageMediaSynchronizer;
use OursBlanc\Xms\Models\Page;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    Storage::fake('public');
});

function putPendingImageFixture(string $path = 'xms-pending/test.png'): string
{
    Storage::disk('public')->put($path, file_get_contents(__DIR__.'/../Fixtures/test-image.png'));

    return $path;
}

function putPendingVideoFixture(string $path = 'xms-pending/test.mp4'): string
{
    Storage::disk('public')->put($path, file_get_contents(__DIR__.'/../Fixtures/test-video.mp4'));

    return $path;
}

function heroPage(array $blocks): Page
{
    return Page::create([
        'locale' => 'fr',
        'slug' => 'media-'.uniqid(),
        'title' => 'X',
        'blocks' => $blocks,
        'seo' => [],
    ]);
}

it('attaches a pending upload into the media library and replaces the path with a media id', function () {
    $path = putPendingImageFixture();

    $page = heroPage([
        ['uuid' => 'u1', 'type' => 'hero', 'data' => ['title' => 'T', 'alignment' => 'left', 'image' => $path]],
    ]);

    app(PageMediaSynchronizer::class)->sync($page);

    $imageValue = $page->fresh()->blocks[0]['data']['image'];

    expect($imageValue)->toBeInt();

    $media = Media::find($imageValue);

    expect($media)->not->toBeNull()
        ->and($media->collection_name)->toBe('block-u1')
        ->and($media->model_id)->toBe($page->id);
});

it('leaves an already-attached media id untouched', function () {
    $page = heroPage([
        ['uuid' => 'u1', 'type' => 'hero', 'data' => ['title' => 'T', 'alignment' => 'left', 'image' => 42]],
    ]);

    app(PageMediaSynchronizer::class)->sync($page);

    expect($page->fresh()->blocks[0]['data']['image'])->toBe(42);
});

it('attaches media inside a repeater via dot-star media field paths', function () {
    $path = putPendingImageFixture();

    $page = heroPage([
        ['uuid' => 'g1', 'type' => 'gallery', 'data' => [
            'columns' => '3',
            'images' => [['image' => $path, 'alt' => 'x']],
        ]],
    ]);

    app(PageMediaSynchronizer::class)->sync($page);

    $imageValue = $page->fresh()->blocks[0]['data']['images'][0]['image'];

    expect($imageValue)->toBeInt();
    expect(Media::find($imageValue)->collection_name)->toBe('block-g1');
});

it('marks orphaned media for deferred deletion when its block is removed', function () {
    $path = putPendingImageFixture();

    $page = heroPage([
        ['uuid' => 'u1', 'type' => 'hero', 'data' => ['title' => 'T', 'alignment' => 'left', 'image' => $path]],
    ]);

    app(PageMediaSynchronizer::class)->sync($page);

    $page->update(['blocks' => []]);
    app(PageMediaSynchronizer::class)->sync($page->fresh());

    $media = $page->fresh()->media()->first();

    expect($media->getCustomProperty('pending_deletion_at'))->not->toBeNull();
});

it('clears the pending deletion flag when the block reappears before the grace period ends', function () {
    $path = putPendingImageFixture();

    $page = heroPage([
        ['uuid' => 'u1', 'type' => 'hero', 'data' => ['title' => 'T', 'alignment' => 'left', 'image' => $path]],
    ]);

    app(PageMediaSynchronizer::class)->sync($page);
    $imageId = $page->fresh()->blocks[0]['data']['image'];

    $page->update(['blocks' => []]);
    app(PageMediaSynchronizer::class)->sync($page->fresh());

    $page->update(['blocks' => [
        ['uuid' => 'u1', 'type' => 'hero', 'data' => ['title' => 'T', 'alignment' => 'left', 'image' => $imageId]],
    ]]);
    app(PageMediaSynchronizer::class)->sync($page->fresh());

    expect(Media::find($imageId)->getCustomProperty('pending_deletion_at'))->toBeNull();
});

it('permanently deletes media whose grace period has elapsed', function () {
    $path = putPendingImageFixture();

    $page = heroPage([
        ['uuid' => 'u1', 'type' => 'hero', 'data' => ['title' => 'T', 'alignment' => 'left', 'image' => $path]],
    ]);

    app(PageMediaSynchronizer::class)->sync($page);
    $imageId = $page->fresh()->blocks[0]['data']['image'];

    $media = Media::find($imageId);
    $media->setCustomProperty('pending_deletion_at', now()->subHour()->toIso8601String());
    $media->save();

    $deleted = app(PageMediaSynchronizer::class)->pruneExpired();

    expect($deleted)->toBe(1)
        ->and(Media::find($imageId))->toBeNull();
});

it('does not delete media whose grace period has not elapsed yet', function () {
    $path = putPendingImageFixture();

    $page = heroPage([
        ['uuid' => 'u1', 'type' => 'hero', 'data' => ['title' => 'T', 'alignment' => 'left', 'image' => $path]],
    ]);

    app(PageMediaSynchronizer::class)->sync($page);
    $imageId = $page->fresh()->blocks[0]['data']['image'];

    $page->update(['blocks' => []]);
    app(PageMediaSynchronizer::class)->sync($page->fresh());

    $deleted = app(PageMediaSynchronizer::class)->pruneExpired();

    expect($deleted)->toBe(0)
        ->and(Media::find($imageId))->not->toBeNull();
});

it('auto-generates a poster for an uploaded video with no explicit poster', function () {
    $path = putPendingVideoFixture();

    $page = heroPage([
        ['uuid' => 'v1', 'type' => 'video', 'data' => ['video' => $path]],
    ]);

    app(PageMediaSynchronizer::class)->sync($page);

    $data = $page->fresh()->blocks[0]['data'];

    expect($data['video'])->toBeInt()
        ->and($data['poster'] ?? null)->toBeInt();

    $poster = Media::find($data['poster']);

    expect($poster->collection_name)->toBe('block-v1')
        ->and($poster->getCustomProperty('is_poster_for'))->toBe($data['video']);
});
