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

        if (strlen($token) < 32 || strlen($token) > 128) {
            return response()->json(['message' => 'Invalid token format'], 401);
        }

        $hashed = hash('sha256', $token);
        $user = User::where('api_token', $hashed)->first();

        if (! $user) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $request->attributes->set('api_user', $user);

        return $next($request);
    }
}
