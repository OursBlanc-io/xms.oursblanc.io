<?php

use Illuminate\Testing\TestResponse;
use OursBlanc\Xms\Models\ApiToken;

function mcpCall(?string $token, string $tool, array $arguments = []): TestResponse
{
    $headers = ['Accept' => 'application/json, text/event-stream'];

    if ($token) {
        $headers['Authorization'] = "Bearer {$token}";
    }

    return test()->postJson('/mcp/xms', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => ['name' => $tool, 'arguments' => $arguments],
    ], $headers);
}

it('rejects a request with no bearer token', function () {
    mcpCall(null, 'list_block_types')
        ->assertStatus(401)
        ->assertJson(['error' => 'Missing bearer token.']);
});

it('rejects a request with an invalid bearer token', function () {
    mcpCall('not-a-real-token', 'list_block_types')
        ->assertStatus(401)
        ->assertJson(['error' => 'Invalid API token.']);
});

it('accepts a request with a valid bearer token', function () {
    ['plainTextToken' => $token] = ApiToken::generate('Claude', ['pages:read']);

    $response = mcpCall($token, 'list_block_types')->assertOk();

    expect($response->json('result.content.0.text'))->toContain('block_types');
});

it('marks the token as used on a successful call', function () {
    ['token' => $apiToken, 'plainTextToken' => $token] = ApiToken::generate('Claude', ['pages:read']);

    expect($apiToken->last_used_at)->toBeNull();

    mcpCall($token, 'list_block_types');

    expect($apiToken->fresh()->last_used_at)->not->toBeNull();
});

it('returns a structured tool error when the token lacks the required ability', function () {
    ['plainTextToken' => $token] = ApiToken::generate('Claude', ['pages:read']);

    $response = mcpCall($token, 'create_page', [
        'locale' => 'fr', 'title' => 'X', 'slug' => 'x', 'blocks' => [],
    ])->assertOk();

    $body = $response->json();

    expect($body['result']['isError'])->toBeTrue()
        ->and($body['result']['content'][0]['text'])->toContain('pages:write');
});
