<?php

use Illuminate\Support\Facades\Queue;
use OursBlanc\Xms\Cache\CacheInvalidator;
use OursBlanc\Xms\Jobs\PurgeCdnCacheJob;
use OursBlanc\Xms\Models\Page;

it('dispatches a purge job with the urls to purge when a page is saved', function () {
    Queue::fake();

    $page = Page::create([
        'locale' => 'fr', 'slug' => 'accueil', 'title' => 'X',
        'blocks' => [], 'seo' => [],
    ]);

    Queue::assertPushed(PurgeCdnCacheJob::class, fn (PurgeCdnCacheJob $job) => $job->urls === $page->urlsToPurge()
    );
});

it('dispatches an additional purge job when a page is published', function () {
    Queue::fake();

    $page = Page::create([
        'locale' => 'fr', 'slug' => 'accueil', 'title' => 'X',
        'blocks' => [], 'seo' => [],
    ]);

    Queue::assertPushed(PurgeCdnCacheJob::class, 1);

    $page->update(['status' => 'published', 'published_at' => now()]);

    Queue::assertPushed(PurgeCdnCacheJob::class, 3);
});

it('the purge job calls the bound cache invalidator with its urls', function () {
    $invalidator = Mockery::mock(CacheInvalidator::class);
    $invalidator->shouldReceive('purgeUrls')->once()->with(['https://oursblanc.test/foo']);

    app()->instance(CacheInvalidator::class, $invalidator);

    (new PurgeCdnCacheJob(['https://oursblanc.test/foo']))->handle($invalidator);
});

it('the purge job does nothing for an empty url list', function () {
    $invalidator = Mockery::mock(CacheInvalidator::class);
    $invalidator->shouldNotReceive('purgeUrls');

    (new PurgeCdnCacheJob([]))->handle($invalidator);
});
