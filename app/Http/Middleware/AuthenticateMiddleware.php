<?php

namespace App\Http\Middleware;

use App\Exceptions\Handler;
use Closure;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthenticateMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, ...$request_access)
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
            goto response;
        }
        if ($user->status == -1) {
            return $this->throwError($request, new AccessDeniedHttpException('Your account was blocked'));
        }
        if ($user->status == 0) {
            return $this->throwError($request, new AccessDeniedHttpException('Your account is inactive'));
        }

        if ($request->method() == 'GET' && !$request->wantsJson()) {
            return $next($request);
        }

        if (in_array((explode('/', $request->path())[1] ?? null), ['import-example', 'export', 'download', 'download-template', 'download-attachment'])) {
            return $next($request);
        }
        if (in_array((explode('/', $request->path())[0] ?? null), ['download', 'download-attachment'])) {
            return $next($request);
        }

        if (in_array(explode('/', $request->path())[0], ['auth', 'dashboard', 'img-uploads', 'navigation', 'import-example', 'export', 'download', 'notifications'])) {
            goto response;
        }

        if (!count($request_access) && isset($request->forceView)) {
            goto response;
        }

        $currrent_url = explode('/', $request->path());
        $user_menu = $user->role->access;
        $menu = DB::table('navigations')->where(['link' => $currrent_url[0]])->first(['action']);
        if (!$menu) {
            if ($user_menu[0] != '*') {
                $this->throwError($request, new AccessDeniedHttpException('Access forbidden'));
            } else {
                goto response;
            }
        }
        $menu_action = json_decode($menu->action ?? '[]') ?? [];
        $action = array();
        if ($user_menu[0] == '*') {
            foreach ($menu_action as $a) {
                $action[$a] = true;
            }
            goto response;
        }
        $user_menu = collect($user_menu);
        $access_link = $user_menu->where('link', $currrent_url[1])->first();
        if (!$access_link) {
            return $this->throwError($request, new AccessDeniedHttpException('Access forbidden'));
        }
        $action = $access_link;
        if (count($request_access)) {
            if (!isset($action[$request_access[0]])) {
                return $this->throwError($request, new AccessDeniedHttpException('Access forbidden'));
            }
            if (!$action[$request_access[0]]) {
                return $this->throwError($request, new AccessDeniedHttpException('Access forbidden'));
            }
        }
        response:
        $response = $next($request);
        $response->header('X-Access', base64_encode(json_encode($action ?? null)));
        return $response;
    }

    private function throwError($request, $throw)
    {
        $e = new Handler(app());
        return $e->render($request, $throw);
    }
}
