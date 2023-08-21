<?php

namespace App\Http\Controllers\CMS;

use App\Exports\WarehouseExport;
use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\MaterialStockDetail;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class MainWarehouseController extends Controller
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
        $items = Warehouse::query();
        $items->where('type', 'main');
        $items->orderBy('code', 'asc');

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
                        ->orWhere('pic_name', 'like', '%' . $q . '%');
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
     * stocks
     *
     * @param  mixed $id
     * @return void
     */
    public function stocks(Request $request, $id)
    {
        $items = Material::query();
        $items->whereHas('stocks', function ($q) use ($id) {
            $q->where('warehouse_id', $id);
        });
        $items->select(['id', 'number', 'name', 'uom']);
        $items->where('status', 1);
        $limit = $request->limit ?? 10;
        $data = $items->paginate((int)$limit)->toArray();
        $data['data'] = collect($data['data'])->map(function ($i) use ($id) {
            $i['stocks'] = MaterialStockDetail::whereHas('material_stock', function ($q) use ($i, $id) {
                $q->where('material_id', $i['id'])
                    ->where('warehouse_id', $id);
            })->with([
                'material_stock:id,project_id',
                'material_stock.project:id,code',
                'purchase_order:id,number,delivery_date'
            ])->get();
            return $i;
        })->all();
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
            'code' => ['required', 'string', Rule::unique(Warehouse::class, 'code')->where('type', 'main')],
            'name' => 'required|string|max:128',
            'address' => 'nullable|string|max:255',
            'pic_name' => 'required|string|max:128',
            'pic_phone' => 'required|string|max:128',
            'status' => 'required|numeric:in:0,1'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();
        $item = new Warehouse();
        $item->code = $data->code;
        $item->name = $data->name;
        $item->type = 'main';
        $item->pic_name = $data->pic_name;
        $item->pic_phone = $data->pic_phone;
        $item->address = $data->address;
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
            'id' => ['required', 'string', Rule::exists(Warehouse::class, 'id')],
            'code' => ['required', 'string', Rule::unique(Warehouse::class, 'code')->where('type', 'main')->ignore($data['id'])],
            'name' => 'required|string|max:128',
            'address' => 'nullable|string|max:255',
            'pic_name' => 'required|string|max:128',
            'pic_phone' => 'required|string|max:128',
            'status' => 'required|numeric:in:0,1'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();
        $item = Warehouse::where('id', $data->id)->first();
        $item->code = $data->code;
        $item->name = $data->name;
        $item->pic_name = $data->pic_name;
        $item->pic_phone = $data->pic_phone;
        $item->address = $data->address;
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
            'id' => ['required', 'string', Rule::exists(Warehouse::class, 'id')],
            'status' => 'required|numeric:in:0,1'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();
        $item = Warehouse::where('id', $data->id)->first();
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
        Warehouse::whereIn('id', $ids)->delete();
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
        return Excel::download(new WarehouseExport($request, 'main'), 'IMS-Main-Warehouse-List.xlsx');
    }
}
