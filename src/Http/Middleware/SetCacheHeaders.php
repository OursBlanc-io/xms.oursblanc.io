<?php

namespace OursBlanc\Xms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCacheHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->isOk() && ! auth()->check()) {
            $response->headers->set('Cache-Control', sprintf(
                'public, s-maxage=%d, max-age=%d',
                (int) config('xms.cache.s_maxage'),
                (int) config('xms.cache.max_age'),
            ));
        } else {
            $response->headers->set('Cache-Control', 'no-store');
        }

        return $response;
    }
}
