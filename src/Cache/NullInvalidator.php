<?php

namespace OursBlanc\Xms\Cache;

/**
 * Default invalidator, used whenever Cloudflare credentials aren't
 * configured (e.g. local development).
 */
class NullInvalidator implements CacheInvalidator
{
    public function purgeUrls(array $urls): void
    {
        // Intentionally empty.
    }
}
