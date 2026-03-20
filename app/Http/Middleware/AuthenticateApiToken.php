<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $hashed = hash('sha256', $token);
        $user = User::where('api_token', $hashed)->first();

        if (! $user) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        auth()->setUser($user);

        return $next($request);
    }
}
