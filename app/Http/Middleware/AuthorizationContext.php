<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizationContext
{
    /**
     * Declarative route metadata consumed by AuthorizationService.
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        return $next($request);
    }
}
