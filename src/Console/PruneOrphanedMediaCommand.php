<?php

namespace OursBlanc\Xms\Console;

use Illuminate\Console\Command;
use OursBlanc\Xms\Media\PageMediaSynchronizer;

class PruneOrphanedMediaCommand extends Command
{
    protected $signature = 'xms:prune-media';

    protected $description = 'Permanently delete page media whose 24h grace period after becoming orphaned has elapsed';

    public function handle(PageMediaSynchronizer $synchronizer): int
    {
        $deleted = $synchronizer->pruneExpired();

        $this->info("Deleted {$deleted} expired media item(s).");

        return self::SUCCESS;
    }
}
