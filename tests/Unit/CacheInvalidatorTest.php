<?php

use Illuminate\Support\Facades\Http;
use OursBlanc\Xms\Cache\CacheInvalidator;
use OursBlanc\Xms\Cache\CloudflareInvalidator;
use OursBlanc\Xms\Cache\NullInvalidator;

it('binds the null invalidator by default', function () {
    config(['xms.cloudflare.zone_id' => null, 'xms.cloudflare.token' => null]);

    expect(app(CacheInvalidator::class))->toBeInstanceOf(NullInvalidator::class);
});

it('binds the Cloudflare invalidator once zone and token are configured', function () {
    config(['xms.cloudflare.zone_id' => 'zone-123', 'xms.cloudflare.token' => 'token-abc']);

    expect(app(CacheInvalidator::class))->toBeInstanceOf(CloudflareInvalidator::class);
});

it('the null invalidator does nothing', function () {
    Http::fake();

    (new NullInvalidator)->purgeUrls(['https://oursblanc.test/foo']);

    Http::assertNothingSent();
});

it('the Cloudflare invalidator posts a purge request with the given urls', function () {
    config(['xms.cloudflare.zone_id' => 'zone-123', 'xms.cloudflare.token' => 'token-abc']);

    Http::fake(['api.cloudflare.com/*' => Http::response(['success' => true])]);

    (new CloudflareInvalidator)->purgeUrls(['https://oursblanc.test/foo', 'https://oursblanc.test/bar']);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.cloudflare.com/client/v4/zones/zone-123/purge_cache'
            && $request->hasHeader('Authorization', 'Bearer token-abc')
            && $request['files'] === ['https://oursblanc.test/foo', 'https://oursblanc.test/bar'];
    });
});

it('the Cloudflare invalidator does not send a request for an empty url list', function () {
    Http::fake();

    (new CloudflareInvalidator)->purgeUrls([]);

    Http::assertNothingSent();
});

it('the Cloudflare invalidator logs a warning instead of throwing on failure', function () {
    config(['xms.cloudflare.zone_id' => 'zone-123', 'xms.cloudflare.token' => 'token-abc']);

    Http::fake(['api.cloudflare.com/*' => Http::response(['success' => false], 500)]);

    (new CloudflareInvalidator)->purgeUrls(['https://oursblanc.test/foo']);
})->throwsNoExceptions();
