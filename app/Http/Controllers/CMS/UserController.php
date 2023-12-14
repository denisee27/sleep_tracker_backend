<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\JobMaster;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * index
     *
     * @param  mixed $request
     * @param  mixed $id
     * @return void
     */
    public function index(Request $request, $id = null)
    {
        $data = [];
        $items = User::query();
        $items->orderBy('nik', 'asc');
        $items->with([
            'role:id,name',
            'superior:id,name'
        ]);

        if (isset($request->filter) && $request->filter) {
            $filter = json_decode($request->filter, true);
            $items->where($filter);
        }

        if(isset($request->self) && $request->self){
            $items->whereNot(function ($q) use ($request){
                $q->where('nik','super-admin')
                ->orWhere('id',auth()->user()->id);
            });
        }

        if ($id == null) {
            if (isset($request->q) && $request->q) {
                $q = $request->q;
                $items->where(function ($query) use ($q) {
                    $query->orWhere('name', 'like', '%' . $q . '%')
                        ->orWhere('email', 'like', '%' . $q . '%')
                        ->orWhere('nik', 'like', '%' . $q . '%')
                        ->orWhere('phone', 'like', '%' . $q . '%')
                        ->orWhereHas('role', function ($query) use ($q) {
                            $query->where('name', 'like', '%' . $q . '%');
                        });
                });
            }
            if (isset($request->limit) && ((int) $request->limit) > 0) {
                $data = $items->paginate(((int) $request->limit))->toArray();
            } else {
                $data['data'] = $items->get();
                $data['total'] = count($data['data']);
            }
        } else {
            $data['data'] = $items->where('id', $id)->first();
            $data['total'] = 1;
        }
        $r = ['status' => Response::HTTP_OK, 'result' => $data];
        return response()->json($r, Response::HTTP_OK);
    }

    /**
     * create
     *
     * @param  mixed $request
     * @return void
     */
    public function create(Request $request)
    {
        $data = json_decode($request->data, true);
        $validator = Validator::make($data, [
            'role_id' => ['required', 'string', Rule::exists(Role::class, 'id')],
            'area_id' => ['required', 'string', Rule::exists(Area::class, 'id')],
            'parent_id' => ['nullable', 'string', Rule::exists(User::class, 'id')],
            'nik' => ['required', 'string', Rule::unique(User::class, 'nik')],
            'name' => 'required|string|max:128',
            'email' => ['required', 'email', Rule::unique(User::class, 'email')],
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:32',
            'status' => 'required|numeric:in:0,1'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();
        $item = new User();
        $item->role_id = $data->role_id;
        $item->area_id = $data->area_id;
        $item->parent_id = $data->parent_id;
        $item->nik = $data->nik;
        $item->name = $data->name;
        $item->email = $data->email;
        $item->phone = $data->phone;
        $item->password = $this->pass_hash($data->password);
        $item->status = $data->status;
        $item->save();
        $r = ['status' => Response::HTTP_OK, 'result' => 'ok'];
        return response()->json($r, Response::HTTP_OK);
    }

    /**
     * update
     *
     * @param  mixed $request
     * @return void
     */
    public function update(Request $request)
    {
        $data = json_decode($request->data, true);
        $validator = Validator::make($data, [
            'id' => ['required', 'string', Rule::exists(User::class, 'id')],
            'name' => 'required|string|max:128',
            'bod' => 'required|date_format:Y-m-d',
            'job' => ['required', 'string', Rule::exists(JobMaster::class, 'id')],
            'gender' => 'required|string:in:male,female',
            'weight' => 'required|numeric',
            'height' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $data = (object) $validator->validated();
        $bmi_category = '';
        $item = User::where('id', $data->id)->first();
        $item->name = $data->name;
        $item->job = $data->job;
        $item->bod = $data->bod;
        $item->gender = $data->gender;
        $item->height = $data->height;
        $item->weight = $data->weight;
        $heightInMeter = $data->height / 100;
        $bmi = $data->weight / ($heightInMeter * $heightInMeter);
        if ($bmi < 25) {
            $bmi_category = 'Normal';
        } elseif ($bmi >= 25 && $bmi < 30) {
            $bmi_category = 'Overweight';
        } else {
            $bmi_category = 'Obese';
        }
        $item->bmi = $bmi_category;
        $item->save();
        $r = ['status' => Response::HTTP_OK, 'result' => 'ok'];
        return response()->json($r, Response::HTTP_OK);
    }

    /**
     * delete
     *
     * @param  mixed $request
     * @return void
     */
    public function delete(Request $request)
    {
        $ids = json_decode($request->getContent());
        User::whereIn('id', $ids)->delete();
        return $this->index($request);
    }

    protected function pass_hash($plain_password)
    {
        return sha1(md5(sha1($plain_password)) . 'Tr1@5M1TR4');
    }
}
