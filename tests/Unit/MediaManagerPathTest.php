<?php

use OursBlanc\Xms\Support\MediaManagerPath;

it('sanitizes a path by stripping dot segments, empty segments, and leading/trailing slashes', function (string $input, string $expected) {
    expect(MediaManagerPath::sanitize($input))->toBe($expected);
})->with([
    ['brand/logos', 'brand/logos'],
    ['/brand/logos/', 'brand/logos'],
    ['brand//logos', 'brand/logos'],
    ['../../etc/passwd', 'etc/passwd'],
    ['brand/../../../etc', 'brand/etc'],
    ['./brand/./logos', 'brand/logos'],
    ['', ''],
    ['..', ''],
    ['\\brand\\logos', 'brand/logos'],
]);

it('validates a single name segment', function () {
    expect(MediaManagerPath::isValidName('logo.png'))->toBeTrue()
        ->and(MediaManagerPath::isValidName(''))->toBeFalse()
        ->and(MediaManagerPath::isValidName('.'))->toBeFalse()
        ->and(MediaManagerPath::isValidName('..'))->toBeFalse()
        ->and(MediaManagerPath::isValidName('a/b'))->toBeFalse()
        ->and(MediaManagerPath::isValidName('a\\b'))->toBeFalse();
});

it('joins segments, trimming slashes and dropping empty ones', function () {
    expect(MediaManagerPath::join('mediacontents', 'brand', 'logos'))->toBe('mediacontents/brand/logos')
        ->and(MediaManagerPath::join('mediacontents', '', 'logos'))->toBe('mediacontents/logos')
        ->and(MediaManagerPath::join('/mediacontents/', '/brand/'))->toBe('mediacontents/brand')
        ->and(MediaManagerPath::join('mediacontents'))->toBe('mediacontents');
});
