<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if ($user && ($user->status ?? 'active') !== 'active') {
            try {
                auth()->logout();
            } catch (\Throwable $e) {
                // ignore
            }

            return response()->json([
                'success' => false,
                'message' => 'Account is inactive.',
            ], 403);
        }

        return $next($request);
    }
}