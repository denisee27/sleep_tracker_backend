<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class PublicApiMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $auth = $request->header('Authorization');
        if (!$auth) {
            return response()->json([
                'success' => false,
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'HTTP_UNAUTHORIZED : Token is required'
            ], Response::HTTP_UNAUTHORIZED);
        } elseif (!Str::startsWith(mb_strtolower($auth), 'bearer')) {
            return response()->json([
                'success' => false,
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'HTTP_UNAUTHORIZED : Unknown token type'
            ], Response::HTTP_UNAUTHORIZED);
        } elseif (str_ireplace('bearer ', '', $auth) != config('app.api_key')) {
            return response()->json([
                'success' => false,
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'HTTP_UNAUTHORIZED : Token is invalid'
            ], Response::HTTP_UNAUTHORIZED);
        }
        return $next($request);
    }
}
