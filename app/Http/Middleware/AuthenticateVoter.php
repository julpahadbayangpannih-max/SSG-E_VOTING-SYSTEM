<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateVoter
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('voter')->check()) {
            return redirect()->route('voter.login')
                ->with('error', 'Please sign in to continue.');
        }

        return $next($request);
    }
}
