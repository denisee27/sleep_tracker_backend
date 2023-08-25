<?php

namespace App\Http\Controllers\CMS;

use App\Exports\CategoryExport;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class SubCategoryController extends Controller
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
        $items = SubCategory::query();
        $items->orderBy('code', 'asc');
        $items->with(['category:id,code,name']);

        if (isset($request->filter) && $request->filter) {
            $filter = json_decode($request->filter, true);
            $items->where($filter);
        }

        if ($id == null) {
            if (isset($request->q) && $request->q) {
                $q = $request->q;
                $items->where(function ($query) use ($q) {
                    $query->orWhere('name', 'like', '%' . $q . '%')
                        ->orWhere('code', 'like', '%' . $q . '%')
                        ->orWhere('description', 'like', '%' . $q . '%')
                        ->orWhereHas('category', function ($query) use ($q) {
                            $query->where('name', 'like', '%' . $q . '%')
                                ->orWhere('code', 'like', '%' . $q . '%');
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
            'category_id' => ['required', 'string', Rule::exists(Category::class, 'id')],
            'name' => 'required|string|max:128',
            'description' => 'nullable|string|max:255',
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
            $getLast = SubCategory::orderBy('code', 'DESC')
                ->sharedLock()
                ->first();
            $lastNumber = (!$getLast) ? 0 : abs(substr($getLast->code, -3));
            $makeNumber = 'SC' . sprintf('%03s', $lastNumber + 1);
            $cekNumber = SubCategory::where('code', $makeNumber)->count();
            if ($cekNumber > 0) {
                DB::rollBack();
                return response()->json([
                    'status' => Response::HTTP_CONFLICT,
                    'message' => 'Try again'
                ], Response::HTTP_CONFLICT);
            }
            $item = new SubCategory();
            $item->category_id = $data->category_id;
            $item->code = $makeNumber;
            $item->name = ucwords($data->name);
            $item->description = $data->description;
            $item->status = $data->status;
            $item->save();
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
            'id' => ['required', 'string', Rule::exists(SubCategory::class, 'id')],
            'category_id' => ['required', 'string', Rule::exists(Category::class, 'id')],
            'name' => 'required|string|max:128',
            'description' => 'nullable|string|max:255',
            'status' => 'required|numeric:in:0,1'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();
        $item = SubCategory::where('id', $data->id)->first();
        $item->category_id = $data->category_id;
        $item->name = ucwords($data->name);
        $item->description = $data->description;
        $item->status = $data->status;
        $item->save();
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
            'id' => ['required', 'string', Rule::exists(Category::class, 'id')],
            'status' => 'required|numeric:in:0,1'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();
        $item = SubCategory::where('id', $data->id)->first();
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
        SubCategory::whereIn('id', $ids)->delete();
        return $this->index($request);
    }

    /**
     * download
     *
     * @param  mixed $request
     * @return void
     */
    public function download(Request $request)
    {
        return Excel::download(new CategoryExport($request), 'AMS-Asset-SubCategories.xlsx');
    }
}
