<?php

namespace App\Http\Controllers\CMS;

use App\Exports\ReportMaterialStockExport;
use App\Http\Controllers\Controller;
use App\Models\MaterialStock;
use App\Models\Project;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ReportMaterialStockController extends Controller
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
            'warehouse_type' => 'nullable|string|in:main,transit,lastmile',
            'warehouses' => 'nullable|array',
            'warehouses.*' => ['string', Rule::exists(Warehouse::class, 'id')],
            'projects' => 'nullable|array',
            'projects.*' => ['string', Rule::exists(Project::class, 'id')],
            'type' => 'required|string|in:good,bad,lost'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $data = (object) $validator->validated();
        $items = MaterialStock::query();
        $items->where('project_id', '!=', 'dummy-001');
        $items->selectRaw("material_stocks.*");
        if (isset($data->warehouse_type)) {
            $items->where('warehouses.type', $data->warehouse_type);
        }
        if (isset($data->warehouses) && count($data->warehouses)) {
            $items->whereIn('warehouse_id', $data->warehouses);
        }
        if (isset($data->projects) && count($data->projects)) {
            $items->whereIn('project_id', $data->projects);
        }
        $items->with([
            'material:id,number,name,uom',
            'warehouse:id,name,type',
            'project:id,code'
        ]);
        $items->withSum('details as current_stock', $data->type . '_stock');
        $items->join('warehouses', 'material_stocks.warehouse_id', '=', 'warehouses.id');
        $items->orderBy('warehouses.type', 'ASC');
        $items->orderBy('warehouses.name', 'ASC');
        $result = $items->paginate(20);
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
            'warehouse_type' => 'nullable|string|in:main,transit,lastmile',
            'warehouses' => 'nullable|string',
            'projects' => 'nullable|string',
            'type' => 'required|string|in:good,bad,lost'
        ]);
        if ($validator->fails()) {
            return response('<h2>Oops!</h2><h3>' . $validator->errors()->first() . '</h3>', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $data = (object)$validator->validated();
        return Excel::download(new ReportMaterialStockExport($data), 'IMS-Report-Material-Stock.xlsx');
    }
}
