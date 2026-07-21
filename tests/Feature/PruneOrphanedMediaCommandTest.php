<?php

use Illuminate\Support\Facades\Storage;
use OursBlanc\Xms\Media\PageMediaSynchronizer;
use OursBlanc\Xms\Models\Page;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

it('deletes expired media via the artisan command', function () {
    Storage::fake('public');
    Storage::disk('public')->put('xms-pending/test.png', file_get_contents(__DIR__.'/../Fixtures/test-image.png'));

    $page = Page::create([
        'locale' => 'fr',
        'slug' => 'prune-cmd',
        'title' => 'X',
        'blocks' => [
            ['uuid' => 'u1', 'type' => 'hero', 'data' => ['title' => 'T', 'alignment' => 'left', 'image' => 'xms-pending/test.png']],
        ],
        'seo' => [],
    ]);

    app(PageMediaSynchronizer::class)->sync($page);
    $imageId = $page->fresh()->blocks[0]['data']['image'];

    $media = Media::find($imageId);
    $media->setCustomProperty('pending_deletion_at', now()->subHour()->toIso8601String());
    $media->save();

    $this->artisan('xms:prune-media')
        ->expectsOutputToContain('Deleted 1 expired media item(s).')
        ->assertSuccessful();

    expect(Media::find($imageId))->toBeNull();
});
