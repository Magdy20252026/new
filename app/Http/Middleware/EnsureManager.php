<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureManager
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isManager(), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
