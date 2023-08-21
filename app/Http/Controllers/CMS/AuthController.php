<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\Navigation;
use App\Models\User;
use App\Traits\ThrottlesLoginTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use ThrottlesLoginTrait;

    /**
     * maxAttempts
     *
     * @var int
     */
    protected $maxAttempts = 6;

    /**
     * decayMinutes
     *
     * @var int
     */
    protected $decayMinutes = 3;

    /**
     * username
     *
     * @var string
     */
    protected $username = 'email';

    /**
     * pass_hash
     *
     * @param  mixed $plain_password
     * @return mixed
     */
    protected function pass_hash($plain_password)
    {
        return sha1(md5(sha1($plain_password)) . 'Tr1@5M1TR4');
    }

    /**
     * login
     *
     * @param  Request $request
     * @return void
     */
    public function login(Request $request)
    {
        $token = null;
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|max:64',
            'password' => 'required|string|'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        $key = 'email';
        $user = User::where($key, $request->email)->first();
        if (!$user) {
            $key = 'nik';
            $user = User::where($key, $request->email)->first();
        }
        if (!$user) {
            $this->incrementLoginAttempts($request);
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => [
                    'email' => [ucwords($key) . ' and/or Password is incorect']
                ]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($user->status != 1) {
            $this->incrementLoginAttempts($request);
            $status = $user->status == 0 ? 'not active yet' : ($user->status == -1 ? 'inactivated' : 'unknown');
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => ['email' => ['Your account is ' . $status]]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($user->password != $this->pass_hash($request->password)) {
            $this->incrementLoginAttempts($request);
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => [
                    'password' => [ucwords($key) . ' and/or Password is incorect']
                ],
                'pass' => $this->pass_hash($request->password)
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if (isset($request->is_refresh) && $request->is_refresh) {
            return $this->refresh($request);
        }
        $token = Auth::login($user);
        $this->clearLoginAttempts($request);
        return $this->createNewToken((string)$token);
    }

    /**
     * profile
     *
     * @return void
     */
    public function profile()
    {
        $user = auth()->user();
        $userData = [
            'name' => $user->name,
            'initials' => $user->initials,
            'email' => $user->email,
            'role' => $user->role->name
        ];
        $response = [
            'status' => Response::HTTP_OK,
            'result' => $userData,
        ];
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * logout
     *
     * @return void
     */
    public function logout()
    {
        Auth::logout();
        $response = [
            'status' => Response::HTTP_OK,
            'message' => 'Successfully logged out'
        ];
        return response()->json($response, Response::HTTP_OK);
    }
    /**
     * refresh
     *
     * @param  mixed $request
     * @return void
     */
    public function refresh(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $user = Auth::user();
        if ($user->password != $this->pass_hash($request->password)) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => [
                    'password' => ['Password is incorect']
                ]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        DB::table('users')->where('id', $user->id)->update(['last_login_at' => Carbon::now()]);
        $user->save();
        $response = [
            'status' => Response::HTTP_OK,
            'result' => [
                'access_token' => Auth::refresh(),
                'token_type' => 'Bearer',
                'timeout' => ((int)config('jwt.timeout'))
            ]
        ];
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * createNewToken
     *
     * @param  mixed $token
     * @param  mixed $plain_password
     * @return void
     */
    protected function createNewToken(string $token)
    {
        $user = Auth::user();
        DB::table('users')->where('id', $user->id)->update(['last_login_at' => Carbon::now()]);
        $userData = [
            'name' => $user->name,
            'initials' => $user->initials,
            'email' => $user->email,
            'role' => $user->role->name
        ];
        return response()->json([
            'status' => Response::HTTP_OK,
            'result' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'timeout' => ((int)config('jwt.timeout')),
                'user' => $userData
            ]
        ], Response::HTTP_OK);
    }
    /**
     * navigation
     *
     * @return void
     */
    public function navigation(Request $request)
    {
        $user = Auth::user();
        if ($user->role->access[0] == '*') {
            $allNav = new NavigationController();
            $request->merge(['filter' => json_encode(['status' => 1])]);
            return $allNav->index($request);
        }
        $user_menu = array();
        foreach (($user->role->access ?? []) as $l) {
            $user_menu[] = $l->link;
        }
        $items_c = Navigation::query();
        $items_c->where('status', 1)->where('parent_id', '!=', null);
        $items_c->whereIn('link', $user_menu);
        $childs = $items_c->pluck('parent_id');
        $items_s = Navigation::query();
        if (isset($request->parent) && $request->parent) {
            $items_s->where('parent_id', $request->parent);
        }
        $items_s->where('status', 1);
        $items_s->where(function ($q) use ($childs, $user_menu) {
            $q->where(function ($qw) use ($user_menu) {
                $qw->where('parent_id', null)
                    ->whereIn('link', $user_menu);
            })->orWhereIn('id', $childs);
        });
        $items_s->select(['id', 'parent_id', 'name', 'icon', 'link']);
        $items_s->with(['childs' => function ($q) use ($user_menu) {
            $q->where('status', 1)
                ->where('parent_id', '!=', null)
                ->whereIn('link', $user_menu)
                ->select(['parent_id', 'name', 'icon', 'link'])
                ->orderBy('position', 'asc');
        }]);
        $items_s->orderBy('position', 'asc');
        $data['data'] = $items_s->get();
        $r = ['status' => Response::HTTP_OK, 'result' => $data];
        return response()->json($r, Response::HTTP_OK);
    }
}
