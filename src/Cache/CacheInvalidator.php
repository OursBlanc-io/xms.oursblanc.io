<?php

namespace OursBlanc\Xms\Cache;

interface CacheInvalidator
{
    /**
     * @param  array<int, string>  $urls
     */
    public function purgeUrls(array $urls): void;
}
