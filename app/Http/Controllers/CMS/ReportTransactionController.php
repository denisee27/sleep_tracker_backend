<?php

namespace App\Http\Controllers\CMS;

use App\Exports\ReportTransactionExport;
use App\Http\Controllers\Controller;
use App\Models\MaterialStockHistory;
use App\Models\Project;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ReportTransactionController extends Controller
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
            'from_date' => 'required|date_format:Y-m-d',
            'to_date' => 'required|date_format:Y-m-d',
            'warehouse_type' => 'nullable|string|in:main,transit,lastmile',
            'warehouses' => 'nullable|array',
            'warehouses.*' => ['string', Rule::exists(Warehouse::class, 'id')],
            'projects' => 'nullable|array',
            'projects.*' => ['string', Rule::exists(Project::class, 'id')],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $data = (object) $validator->validated();
        $items = MaterialStockHistory::query();
        $items->whereBetween('transaction_date', [$data->from_date, $data->to_date]);
        $items->where('project_id', '!=', 'dummy-001');
        if (isset($data->warehouse_type)) {
            $items->whereHas('warehouse', function ($q) use ($data) {
                $q->where('type', $data->warehouse_type);
            });
        }
        if (isset($data->warehouses) && count($data->warehouses)) {
            $items->whereIn('warehouse_id', $data->warehouses);
        }
        if (isset($data->projects) && count($data->projects)) {
            $items->whereIn('project_id', $data->projects);
        }
        $items->with([
            'material:id,number,name,uom',
            'warehouse:id,code,name,type',
            'project:id,code,name',
            'material_to_site:id,ticket_number,from_warehouse'
        ]);
        $items->orderBy('transaction_date', 'ASC');
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
            'from_date' => 'required|date_format:Y-m-d',
            'to_date' => 'required|date_format:Y-m-d',
            'warehouse_type' => 'nullable|string|in:main,transit,lastmile',
            'warehouses' => 'nullable|string',
            'projects' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response('<h2>Oops!</h2><h3>' . $validator->errors()->first() . '</h3>', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $data = (object)$validator->validated();
        return Excel::download(new ReportTransactionExport($data), 'IMS-Report-Transaction.xlsx');
    }
}
