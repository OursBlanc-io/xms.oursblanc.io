<?php

use OursBlanc\Xms\Support\SsrfGuard;

it('allows a public https url', function () {
    SsrfGuard::assertSafeUrl('https://example.com/image.png');
})->throwsNoExceptions();

it('rejects a non-http scheme', function () {
    expect(fn () => SsrfGuard::assertSafeUrl('file:///etc/passwd'))
        ->toThrow(InvalidArgumentException::class, 'Only http/https URLs are allowed.');
});

it('rejects a url with no host', function () {
    expect(fn () => SsrfGuard::assertSafeUrl('https:///path'))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects a loopback address', function () {
    expect(fn () => SsrfGuard::assertSafeUrl('http://127.0.0.1/admin'))
        ->toThrow(InvalidArgumentException::class, 'disallowed private/internal address');
});

it('rejects a private network address', function () {
    expect(fn () => SsrfGuard::assertSafeUrl('http://10.0.0.5/'))
        ->toThrow(InvalidArgumentException::class, 'disallowed private/internal address');

    expect(fn () => SsrfGuard::assertSafeUrl('http://192.168.1.1/'))
        ->toThrow(InvalidArgumentException::class, 'disallowed private/internal address');
});

it('rejects link-local metadata addresses', function () {
    expect(fn () => SsrfGuard::assertSafeUrl('http://169.254.169.254/latest/meta-data/'))
        ->toThrow(InvalidArgumentException::class, 'disallowed private/internal address');
});

it('rejects a host that cannot be resolved', function () {
    expect(fn () => SsrfGuard::assertSafeUrl('http://this-host-does-not-exist.invalid/'))
        ->toThrow(InvalidArgumentException::class, 'Could not resolve host');
});
