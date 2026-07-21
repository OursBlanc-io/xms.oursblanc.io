<?php

use OursBlanc\Xms\Models\ApiToken;

it('generates a token, storing only its sha256 hash', function () {
    ['token' => $token, 'plainTextToken' => $plainTextToken] = ApiToken::generate('Claude', ['pages:read']);

    expect($plainTextToken)->toBeString()->and(strlen($plainTextToken))->toBeGreaterThan(30)
        ->and($token->token)->toBe(hash('sha256', $plainTextToken))
        ->and($token->token)->not->toBe($plainTextToken);
});

it('finds a token by its plaintext value', function () {
    ['plainTextToken' => $plainTextToken] = ApiToken::generate('Claude', ['pages:read']);

    $found = ApiToken::findByPlainTextToken($plainTextToken);

    expect($found)->not->toBeNull()
        ->and(ApiToken::findByPlainTextToken('wrong-token'))->toBeNull();
});

it('checks abilities', function () {
    ['token' => $token] = ApiToken::generate('Claude', ['pages:read', 'pages:write']);

    expect($token->can('pages:read'))->toBeTrue()
        ->and($token->can('pages:publish'))->toBeFalse();
});

it('hides the token hash from array/json representation', function () {
    ['token' => $token] = ApiToken::generate('Claude', ['pages:read']);

    expect($token->toArray())->not->toHaveKey('token');
});
