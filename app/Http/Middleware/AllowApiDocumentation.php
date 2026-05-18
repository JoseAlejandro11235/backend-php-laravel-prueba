<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowApiDocumentation
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment(['local', 'testing']) || config('app.debug')) {
            return $next($request);
        }

        abort(403, 'API documentation is not available in this environment.');
    }
}
