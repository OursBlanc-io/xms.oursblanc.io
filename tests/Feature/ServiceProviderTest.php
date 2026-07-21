<?php

use OursBlanc\Xms\XmsServiceProvider;

it('registers the xms service provider', function () {
    expect(app()->getProviders(XmsServiceProvider::class))->not->toBeEmpty();
});

it('merges the xms config', function () {
    expect(config('xms.default_locale'))->toBe('fr');
});
