<?php

namespace App\Http\Controllers\PublicApi;

use App\Http\Controllers\Controller;
use App\Models\JobPosition;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PublicApiController extends Controller
{
    /**
     * index
     *
     * @param  mixed $request
     * @param  mixed $id
     * @return void
     */
    public function get_hierarchy(Request $request)
    {
        $data = [];
        $items = JobPosition::query();
        $items->selectRaw("id,job_position_id,name");
        $items->with([
            'warehouses',
            'warehouses.warehouse:id,name',
            'parent:id,name'
        ]);
        $items->where('status', 1);
        $items->orderBy('name', 'asc');

        if (isset($request->q) && $request->q) {
            $q = $request->q;
            $items->where(function ($query) use ($q) {
                $query->orWhere('name', 'like', '%' . $q . '%');
            });
        }

        $data['data'] = $items->get()->map(function ($i) {
            $wh = $i->warehouses->map(function ($w) {
                return $w->warehouse->name;
            });
            $e['id'] = $i->id;
            $e['name'] = $i->name;
            $e['responsible_warhouses'] = $wh;
            $e['superior'] = $i->parent;
            return (object)$e;
        });
        $data['total'] = count($data['data']);
        $r = ['success' => true, 'code' => Response::HTTP_OK, 'result' => $data];
        return response()->json($r, Response::HTTP_OK);
    }

    /**
     * create
     *
     * @param  mixed $request
     * @return void
     */
    public function create_user(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'hierarchy_id' => ['required', 'string', Rule::exists(JobPosition::class, 'id')],
            'nik' => ['required', 'string', 'max:64', Rule::unique(User::class, 'nik')],
            'name' => 'required|string|max:128',
            'email' => ['required', 'email', Rule::unique(User::class, 'email')],
            'encrypted_password' => 'required|string',
            'phone' => 'required|string|max:32'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();
        $item = new User();
        $item->job_position_id = $data->hierarchy_id;
        $item->nik = $data->nik;
        $item->name = $data->name;
        $item->email = $data->email;
        $item->phone = $data->phone;
        $item->password = $data->encrypted_password;
        $item->status = 1;
        $item->save();
        $r =  [
            'success' => true,
            'code' => Response::HTTP_OK,
            'result' => ['id' => $item->id]
        ];
        return response()->json($r, Response::HTTP_OK);
    }

    /**
     * update
     *
     * @param  mixed $request
     * @return void
     */
    public function update_user(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nik' => ['required', 'string', Rule::exists(User::class, 'nik')],
            'hierarchy_id' => ['nullable', 'string', Rule::exists(JobPosition::class, 'id')],
            'name' => 'required|string|max:128',
            'email' => ['required', 'email', Rule::unique(User::class, 'email')->ignore($request->nik, 'nik')],
            'encrypted_password' => 'required|string',
            'phone' => 'nullable|string|max:32'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();
        $item = User::where('nik', $data->nik)->first();
        if (isset($data->hierarchy_id) && $data->hierarchy_id) {
            $item->job_position_id = $data->hierarchy_id;
        }
        $item->nik = $data->nik;
        $item->name = $data->name;
        $item->email = $data->email;
        $item->phone = $data->phone;
        $item->password = $data->encrypted_password;
        $item->save();
        $r =  [
            'success' => true,
            'code' => Response::HTTP_OK,
            'result' => ['id' => $item->id]
        ];
        return response()->json($r, Response::HTTP_OK);
    }

    /**
     * set_user_active_status
     *
     * @param  mixed $request
     * @return void
     */
    public function set_user_active_status(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nik' => ['required', 'string', Rule::exists(User::class, 'nik')],
            'status' => 'required|numeric:in:0,1'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();
        $item = User::where('nik', $data->nik)->first();
        $item->status = $data->status;
        $item->save();
        $r = ['success' => 'true', 'status' => Response::HTTP_OK, 'result' => 'ok'];
        return response()->json($r, Response::HTTP_OK);
    }

    /**
     * change_user_password
     *
     * @param  mixed $request
     * @return void
     */
    public function change_user_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nik' => ['required', 'string', Rule::exists(User::class, 'nik')],
            'encrypted_password' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();
        $item = User::where('nik', $data->nik)->first();
        $item->password = $data->encrypted_password;
        $item->save();
        $r =  [
            'success' => true,
            'code' => Response::HTTP_OK,
            'result' => ['id' => $item->id]
        ];
        return response()->json($r, Response::HTTP_OK);
    }

    /**
     * delete
     *
     * @param  mixed $request
     * @return void
     */
    public function delete_user(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nik' => ['required', 'string', Rule::exists(User::class, 'nik')]
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $data = (object) $validator->validated();
        User::where('nik', $data->nik)->delete();
        $r = [
            'success' => true,
            'code' => Response::HTTP_OK,
            'result' => 'ok'
        ];
        return response()->json($r, Response::HTTP_OK);
    }
}
