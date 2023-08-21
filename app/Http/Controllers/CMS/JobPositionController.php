<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\JobPosition;
use App\Models\JobPositionWarehouse;
use App\Models\Role;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class JobPositionController extends Controller
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
        $items = JobPosition::query();
        $items->orderBy('name', 'asc');
        $items->with(['parent', 'role:id,name', 'warehouses']);

        if (isset($request->filter) && $request->filter) {
            $filter = json_decode($request->filter, true);
            $items->where($filter);
        }

        if ($id == null) {
            if (isset($request->q) && $request->q) {
                $q = $request->q;
                $items->where(function ($query) use ($q) {
                    $query->orWhere('name', 'like', '%' . $q . '%')
                        ->orWhereHas('parent', function ($query) use ($q) {
                            $query->where('name', 'like', '%' . $q . '%');
                        })
                        ->orWhereHas('role', function ($query) use ($q) {
                            $query->where('name', 'like', '%' . $q . '%');
                        })
                        ->orWhereHas('warehouses', function ($query) use ($q) {
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
            $items->with('warehouses.warehouse:id,name');
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
            'job_position_id' => ['nullable', 'string', Rule::exists(JobPosition::class, 'id')],
            'role_id' => ['required', 'string', Rule::exists(Role::class, 'id')],
            'warehouses' => ['nullable', 'array'],
            'warehouses.*' => ['string', Rule::exists(Warehouse::class, 'id')],
            'name' => 'required|string|max:128',
            'status' => 'required|numeric:in:0,1'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();
        DB::beginTransaction();
        try {
            $item = new JobPosition();
            $item->job_position_id = $data->job_position_id;
            $item->role_id = $data->role_id;
            $item->name = $data->name;
            $item->status = $data->status;
            $item->save();
            if (isset($data->warehouses) && count($data->warehouses)) {
                foreach ($data->warehouses as $warehouse) {
                    $jobPositionWarehouse = new JobPositionWarehouse();
                    $jobPositionWarehouse->job_position_id = $item->id;
                    $jobPositionWarehouse->warehouse_id = $warehouse;
                    $jobPositionWarehouse->save();
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
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
            'id' => ['required', 'string', Rule::exists(JobPosition::class, 'id')],
            'job_position_id' => ['nullable', 'string', Rule::exists(JobPosition::class, 'id')],
            'role_id' => ['required', 'string', Rule::exists(Role::class, 'id')],
            'warehouses' => ['nullable', 'array'],
            'warehouses.*' => ['string', Rule::exists(Warehouse::class, 'id')],
            'name' => 'required|string|max:128',
            'status' => 'required|numeric:in:0,1'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();
        DB::beginTransaction();
        try {
            $item = JobPosition::where('id', $data->id)->first();
            $item->job_position_id = $data->job_position_id;
            $item->role_id = $data->role_id;
            $item->name = $data->name;
            $item->status = $data->status;
            $item->save();
            $item->warehouses()->whereNotIn('warehouse_id', collect($data->warehouses ?? []))->delete();
            if (isset($data->warehouses) && count($data->warehouses)) {
                foreach ($data->warehouses as $warehouse) {
                    $jobPositionWarehouse =  JobPositionWarehouse::firstOrNew(['job_position_id' => $item->id, 'warehouse_id' => $warehouse]);
                    $jobPositionWarehouse->job_position_id = $item->id;
                    $jobPositionWarehouse->warehouse_id = $warehouse;
                    $jobPositionWarehouse->save();
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
        $r = ['status' => Response::HTTP_OK, 'result' => 'ok'];
        return response()->json($r, Response::HTTP_OK);
    }

    /**
     * set_status
     *
     * @param  mixed $request
     * @return void
     */
    public function set_status(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'string', Rule::exists(JobPosition::class, 'id')],
            'status' => 'required|numeric:in:0,1'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();
        $item = JobPosition::where('id', $data->id)->first();
        $item->status = $data->status;
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
        JobPosition::whereIn('id', $ids)->delete();
        return $this->index($request);
    }
}
