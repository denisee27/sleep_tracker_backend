<?php

namespace App\Http\Controllers\CMS;

use App\Exports\ReportMaterialStockExport;
use App\Exports\ReportSiteExport;
use App\Exports\ReportTransactionExport;
use App\Http\Controllers\Controller;
use App\Models\MaterialStockHistory;
use App\Models\MaterialToSiteDetailPhoto;
use App\Models\Project;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ReportSiteController extends Controller
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
        $items = MaterialToSiteDetailPhoto::query();
        $items->whereHas('material_to_site_detail', function ($q) use ($data) {
            $q->whereHas('material_to_site', function ($q) use ($data) {
                $q->whereBetween('request_date', [$data->from_date, $data->to_date])
                    ->when(isset($data->warehouse_type), function ($q) use ($data) {
                        $q->whereHas('warehouse', function ($q) use ($data) {
                            $q->where('type', $data->warehouse_type);
                        });
                    })->when(isset($data->warehouses) && count($data->warehouses), function ($q) use ($data) {
                        $q->whereIn('from_warehouse', $data->warehouses);
                    })->when(isset($data->projects) && count($data->projects), function ($q) use ($data) {
                        $q->whereIn('project_id', $data->projects);
                    });
            });
        });
        $items->with([
            'material_to_site_detail:id,material_to_site_id,material_id',
            'material_to_site_detail.material:id,number,name',
            'material_to_site_detail.material_to_site:id,project_id,from_warehouse,request_date,number,section_name',
            'material_to_site_detail.material_to_site.from_warehouse:id,name',
            'material_to_site_detail.material_to_site.project:id,code,name'
        ]);
        $items->orderBy('created_at', 'ASC');
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
        return Excel::download(new ReportSiteExport($data), 'IMS-Report-Site-LongLat.xlsx');
    }
}
