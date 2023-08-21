<?php

namespace App\Http\Controllers\CMS;

use App\Exports\ReportStockAlertExport;
use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ReportStockAlertController extends Controller
{
    /**
     * index
     *
     * @param  mixed $request
     * @param  mixed $id
     * @return void
     */
    public function view(Request $request)
    {
        $data = json_decode($request->data, true);
        $validator = Validator::make($data, [
            'warehouse_type' => 'required|string|in:main,transit,lastmile',
            'warehouses' => 'nullable|array',
            'warehouses.*' => ['string', Rule::exists(Warehouse::class, 'id')],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $data = (object) $validator->validated();
        $items = Material::query();
        $items->where('status', 1);
        $items->select(['id', 'number', 'name', 'uom', 'minimum_stock']);
        $items->whereHas('stocks');
        $items->with([
            'stocks' => function ($q) use ($data) {
                $q->where('project_id', '!=', 'dummy-001')
                    ->whereHas('warehouse', function ($q) use ($data) {
                        $q->where('type', $data->warehouse_type);
                    })->when(isset($data->warehouses) && count($data->warehouses), function ($q) use ($data) {
                        $q->whereIn('warehouse_id', $data->warehouses);
                    })
                    ->withSum('details as stock', 'good_stock')
                    ->with(['warehouse:id,name']);
            }
        ]);
        $res = $items->get()->map(function ($i) use ($data) {
            $i->total_stock = collect($i->stocks)->sum('stock');
            $i->warehouse = collect($i->stocks)->groupBy('warehouse_id')->map(function ($e) {
                $firstRow = $e->first();
                return [
                    'id' => $firstRow->warehouse_id,
                    'stock' => $e->sum('stock'),
                    'name' => $firstRow->warehouse->name
                ];
            })->values()->filter(function ($e) use ($i, $data) {
                if (!isset($i->minimum_stock[$data->warehouse_type])) {
                    return false;
                }
                return $e['stock'] < ((float)$i->minimum_stock[$data->warehouse_type]);
            })->all();
            return $i;
        })->filter(function ($i) use ($data) {
            return collect($i->warehouse)->filter(function ($e) use ($i, $data) {
                return $e['stock'] < ((float)$i->minimum_stock[$data->warehouse_type]);
            })->count() > 0;
        })->paginate(20);
        $result = $res;
        $r = ['status' => Response::HTTP_OK, 'result' => $result];
        return response()->json($r, Response::HTTP_OK);
    }

    /**
     * download
     *
     * @param  mixed $request
     * @return void
     */
    public function download(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_type' => 'required|string|in:main,transit,lastmile',
            'warehouses' => 'nullable|string'
        ]);
        if ($validator->fails()) {
            return response('<h2>Oops!</h2><h3>' . $validator->errors()->first() . '</h3>', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $data = (object)$validator->validated();
        return Excel::download(new ReportStockAlertExport($data), 'IMS-Report-Stock-Alert.xlsx');
    }
}
