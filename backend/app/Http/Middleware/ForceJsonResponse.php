<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Force "Accept: application/json" : les erreurs de validation (422) restent en JSON, jamais en redirection HTML. */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
