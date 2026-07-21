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
        $bearerToken = $request->bearerToken();

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
