<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the private admin panel out of search results.
 *
 * The header covers every response, including redirects, error pages and
 * image downloads, which a meta tag in the layout would miss. robots.txt
 * must still allow crawling: a crawler blocked there never sees this header
 * and may index the bare URL anyway.
 */
class PreventSearchIndexing
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
