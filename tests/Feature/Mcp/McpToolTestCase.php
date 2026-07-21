<?php

namespace OursBlanc\Xms\Tests\Feature\Mcp;

use OursBlanc\Xms\Models\ApiToken;
use OursBlanc\Xms\Models\Page;

/**
 * Shared setup for in-process MCP tool tests: `XmsMcpServer::tool(...)` runs
 * tools directly (bypassing HTTP/the auth middleware), so each tool's own
 * `authorize()` call needs an ApiToken bound in the container beforehand, and
 * Page::$authorResolver needs to be set — exactly what AuthenticateMcpToken
 * would otherwise do for a real request.
 */
trait McpToolTestCase
{
    protected function actingAsApiToken(array $abilities = ['pages:read', 'pages:write', 'pages:publish']): ApiToken
    {
        ['token' => $apiToken] = ApiToken::generate('Test token', $abilities);

        app()->instance(ApiToken::class, $apiToken);

        Page::$authorResolver = fn () => ['type' => 'api_token', 'id' => (string) $apiToken->id];

        return $apiToken;
    }
}
