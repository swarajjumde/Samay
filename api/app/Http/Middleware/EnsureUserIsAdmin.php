<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sirf admin (service owner) ko aage jaane deta hai.
 * Iska chhota naam 'admin' bootstrap/app.php mein register hai.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Only the service owner can do this.');
        }

        return $next($request);
    }
}
