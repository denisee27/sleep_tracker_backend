<?php

namespace App\Http\Middleware;

use App\Exceptions\Handler;
use Closure;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class MobileAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (Exception $ex) {
            return $this->throwError($request, $ex);
        }
        if ($user === false) {
            return $this->throwError($request, new AuthorizationException('Token is invalid'));
        }
        if ($request->path() == 'auth/logout') {
            return $next($request);
        }
        if ($user->status == -1) {
            return $this->throwError($request, new AccessDeniedHttpException('Your account was blocked'));
        }
        if ($user->status == 0) {
            return $this->throwError($request, new AccessDeniedHttpException('Your account is inactive'));
        }
        if (!$user->job_position->role->allow_mobile_login) {
            return $this->throwError($request, new AccessDeniedHttpException('You can\'t access this api'));
        }

        return $next($request);
    }

    private function throwError($request, $throw)
    {
        $e = new Handler(app());
        return $e->render($request, $throw);
    }
}
