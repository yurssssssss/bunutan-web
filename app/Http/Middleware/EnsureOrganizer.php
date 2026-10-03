<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizer
{
    /** Only a browser that signed in with the organizer password gets through. */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('roulette_organizer')) {
            return redirect()->route('organizer.login');
        }

        return $next($request);
    }
}
