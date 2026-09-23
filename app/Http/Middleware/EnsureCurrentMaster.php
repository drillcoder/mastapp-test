<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentMaster
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->attributes->get('current_master') === null) {
            return response()->json(['error' => 'Master not found'], 401);
        }

        return $next($request);
    }
}
