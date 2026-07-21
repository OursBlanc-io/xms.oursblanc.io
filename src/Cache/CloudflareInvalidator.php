<?php

namespace OursBlanc\Xms\Cache;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareInvalidator implements CacheInvalidator
{
    public function purgeUrls(array $urls): void
    {
        $urls = array_values(array_filter($urls));

        if ($urls === []) {
            return;
        }

        $zoneId = config('xms.cloudflare.zone_id');

        $response = Http::withToken(config('xms.cloudflare.token'))
            ->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache", [
                'files' => $urls,
            ]);

        if (! $response->successful()) {
            Log::warning('xms: Cloudflare cache purge failed.', [
                'urls' => $urls,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }
}
