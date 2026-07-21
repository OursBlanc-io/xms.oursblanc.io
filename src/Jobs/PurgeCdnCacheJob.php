<?php

namespace OursBlanc\Xms\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use OursBlanc\Xms\Cache\CacheInvalidator;

class PurgeCdnCacheJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /**
     * @param  array<int, string>  $urls
     */
    public function __construct(public array $urls) {}

    public function handle(CacheInvalidator $invalidator): void
    {
        if ($this->urls === []) {
            return;
        }

        $invalidator->purgeUrls($this->urls);
    }
}
