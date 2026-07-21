<?php

namespace OursBlanc\Xms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use OursBlanc\Xms\Models\ApiToken;
use OursBlanc\Xms\Models\Page;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMcpToken
{
    public function handle(Request $request, Closure $next): Response
    {
        // Some MCP clients (e.g. Claude's "custom connector" UI) only accept
        // a plain URL, with no way to set a custom Authorization header —
        // falling back to a `token` query parameter lets those still work,
        // at the cost of the token appearing in server access logs and
        // browser history for that client.
        $bearerToken = $request->bearerToken() ?? $request->query('token');

        if (! $bearerToken) {
            return response()->json(['error' => 'Missing bearer token.'], 401);
        }

        $apiToken = ApiToken::findByPlainTextToken($bearerToken);

        if (! $apiToken) {
            return response()->json(['error' => 'Invalid API token.'], 401);
        }

        $apiToken->markAsUsed();

        app()->instance(ApiToken::class, $apiToken);

        Page::$authorResolver = fn () => ['type' => 'api_token', 'id' => (string) $apiToken->id];

        try {
            return $next($request);
        } finally {
            Page::$authorResolver = null;
        }
    }
}
