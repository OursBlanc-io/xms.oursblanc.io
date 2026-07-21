<?php

namespace OursBlanc\Xms\Listeners;

use OursBlanc\Xms\Events\PagePublished;
use OursBlanc\Xms\Events\PageSaved;
use OursBlanc\Xms\Events\PageUnpublished;
use OursBlanc\Xms\Jobs\PurgeCdnCacheJob;

class DispatchCdnPurge
{
    public function handle(PageSaved|PagePublished|PageUnpublished $event): void
    {
        PurgeCdnCacheJob::dispatch($event->page->urlsToPurge());
    }
}
