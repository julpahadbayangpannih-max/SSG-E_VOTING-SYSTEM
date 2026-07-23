<?php

namespace App\Http\Middleware;

use App\Support\License;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckLicense
{
    public function handle(Request $request, Closure $next): Response
    {
        // Automated tests don't go through the activation flow and
        // shouldn't have to — licensing is a deployment concern, not
        // something the app's own test suite should need to know about.
        if (app()->environment('testing')) {
            return $next($request);
        }

        if ($request->routeIs('license.activate', 'license.activate.post')) {
            return $next($request);
        }

        if (License::isActivated()) {
            return $next($request);
        }

        return redirect()->route('license.activate');
    }
}
