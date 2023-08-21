<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\MaterialStockDetail;
use App\Models\MaterialStockHistory;
use App\Models\MaterialToSite;
use App\Models\UsedMaterialReturn;
use App\Models\UsedMaterialReturnDetail;
use App\Models\Warehouse;
use App\Services\ApprovalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UsedMaterialReturnController extends Controller
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
        $items = UsedMaterialReturn::query();

        if (!isset($request->forceView)) {
            $items->when(!auth()->user()->is_superadmin, function ($q) {
                $q->where(function ($q) {
                    $q->where('created_by', auth()->user()->id)
                        ->orWhereHas('approvals', function ($q) {
                            $q->where('job_position_id', auth()->user()->job_position_id);
                        });
                });
            });
        }

        $items->orderBy('number', 'desc');
        $items->with([
            'material_to_site:id,number',
            'warehouse:id,code,name',
            'creator:id,job_position_id,name',
            'creator.job_position:id,role_id,name',
            'creator.job_position.role:id,name',
            'approvals' => function ($q) {
                $q->select(['job_position_id', 'type', 'type_id', 'status', 'status_name', 'status_order'])
                    ->where('status', 0)
                    ->where('show_notification', 1)
                    ->with(['job_position:id,role_id', 'job_position.role:id,name']);
            }
        ]);

        if (isset($request->filter) && $request->filter) {
            $filter = json_decode($request->filter, true);
            $items->where($filter);
        }

        if ($id == null) {

            if (isset($request->q) && $request->q) {
                $q = $request->q;
                $items->where(function ($query) use ($q) {
                    $query->orWhere('number', 'like', '%' . $q . '%')
                        ->orWhereHas('warehouse', function ($query) use ($q) {
                            $query->where('code', 'like', '%' . $q . '%')
                                ->orWhere('name', 'like', '%' . $q . '%');
                        })
                        ->orWhereHas('details', function ($query) use ($q) {
                            $query->whereHas('material', function ($query) use ($q) {
                                $query->where('number', 'like', '%' . $q . '%')
                                    ->orWhere('name', 'like', '%' . $q . '%');
                            });
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
            $items->with([
                'details',
                'details.material:id,category_id,number,name,uom',
                'details.material.category:id,name',
                'creator:id,name,job_position_id',
                'creator.job_position:id,role_id,name',
                'creator.job_position.role:id,name',
                'approvals' => function ($q) {
                    $q->select(['job_position_id', 'type', 'type_id', 'status', 'status_name', 'status_order', 'remarks', 'show_notification', 'updated_by', 'updated_at'])
                        ->with(['job_position:id,role_id,name', 'job_position.role:id,name', 'updater:id,name']);
                }
            ]);
            $data['data'] = $items->when(isset($request->transaction_id) && $request->transaction_id, function ($q) use ($id) {
                $q->where('number', $id);
            }, function ($q) use ($id) {
                $q->where('id', $id);
            })->first();
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
            'material_to_site_id' => ['required', 'string', Rule::exists(MaterialToSite::class, 'id')],
            'return_date' => 'required|date_format:Y-m-d',
            'notes' => 'nullable|string|max:255',
            'details' => 'required|array',
            'details.*.material_id' => ['required', 'string', Rule::exists(Material::class, 'id')],
            'details.*.qty' => 'required|numeric|min:0'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();
        $mts = MaterialToSite::findOrFail($data->material_to_site_id);
        $warehouse = Warehouse::findOrFail($mts->from_warehouse);
        if ($warehouse->in_stock_opname) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => ['material_to_site_id' => ['Stock opname is underway at this warehouse']]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            $getLast = UsedMaterialReturn::whereDate('created_at', Carbon::now()->format('Y-m-d'))
                ->orderBy('created_at', 'DESC')
                ->orderBy('number', 'DESC')
                ->sharedLock()
                ->first();
            $lastNumber = (!$getLast) ? 0 : abs(substr($getLast->number, -3));
            $makeNumber = Carbon::now()->format('ymd') . 'USMR' . sprintf('%03s', $lastNumber + 1);
            $cekNumber = UsedMaterialReturn::where('number', $makeNumber)->count();
            if ($cekNumber > 0) {
                DB::rollBack();
                return response()->json([
                    'status' => Response::HTTP_CONFLICT,
                    'message' => 'Try again'
                ], Response::HTTP_CONFLICT);
            }

            $item = new UsedMaterialReturn();
            $item->number = $makeNumber;
            $item->material_to_site_id = $data->material_to_site_id;
            $item->warehouse_id = $warehouse->id;
            $item->warehouse_type = $warehouse->type;
            $item->return_date = $data->return_date;
            $item->notes = $data->notes;
            $item->created_by = auth()->user()->id;
            $item->save();

            foreach ($data->details as $_s) {
                $stock = (object)$_s;
                $mdDetail = new UsedMaterialReturnDetail();
                $mdDetail->used_material_return_id = $item->id;
                $mdDetail->material_id = $stock->material_id;
                $mdDetail->qty = $stock->qty;
                $mdDetail->save();
            }

            (new ApprovalService($item, 'used-material-return'))->createApproval();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
        $r = ['status' => Response::HTTP_OK, 'result' => $item];
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
            'id' => ['required', 'string', Rule::exists(UsedMaterialReturn::class, 'id')],
            'material_to_site_id' => ['required', 'string', Rule::exists(MaterialToSite::class, 'id')],
            'return_date' => 'required|date_format:Y-m-d',
            'notes' => 'nullable|string|max:255',
            'details' => 'required|array',
            'details.*.material_id' => ['required', 'string', Rule::exists(Material::class, 'id')],
            'details.*.qty' => 'required|numeric|min:0'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();
        $mts = MaterialToSite::findOrFail($data->material_to_site_id);
        $warehouse = Warehouse::findOrFail($mts->from_warehouse);
        if ($warehouse->in_stock_opname) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => ['material_to_site_id' => ['Stock opname is underway at this warehouse']]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {

            $item = UsedMaterialReturn::find($data->id);
            $item->material_to_site_id = $data->material_to_site_id;
            $item->warehouse_id = $warehouse->id;
            $item->warehouse_type = $warehouse->type;
            $item->return_date = $data->return_date;
            $item->notes = $data->notes;
            $item->updated_by = auth()->user()->id;

            if ($item->status == -1) {
                $approvals = new ApprovalService($item, 'used-material-return');
                $approvals->remove();
                $item->status = 0;
                $approvals->createApproval();
            }

            $item->save();
            $item->details()->whereNotIn('material_id', collect($data->details ?? [])->pluck('material_id'))->forceDelete();

            foreach ($data->details as $_s) {
                $stock = (object)$_s;
                $mdDetail = UsedMaterialReturnDetail::firstOrNew(['used_material_return_id' => $item->id, 'material_id' => $stock->material_id]);
                $mdDetail->used_material_return_id = $item->id;
                $mdDetail->material_id = $stock->material_id;
                $mdDetail->qty = $stock->qty;
                $mdDetail->save();
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
        $r = ['status' => Response::HTTP_OK, 'result' => $item];
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
        foreach (UsedMaterialReturn::whereIn('id', $ids)->get() as $d) {
            (new ApprovalService($d, 'used-material-return'))->remove([$d->id], false);
            $d->delete();
        }
        return $this->index($request);
    }

    /**
     * approve
     *
     * @param  mixed $request
     * @return void
     */
    public function approve(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'string', Rule::exists(UsedMaterialReturn::class, 'id')]
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
            $item = UsedMaterialReturn::where('id', $data->id)->first();
            if (!$item->need_approval) {
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'This data doesn\'t require approval at this time'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            (new ApprovalService($item, 'used-material-return'))->approve();

            if ($item->status == 1) {
                $item->closed_date = Carbon::now();
                $item->save();
                $this->updateStock($item, true);
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
     * reject
     *
     * @param  mixed $request
     * @return void
     */
    public function reject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'string', Rule::exists(UsedMaterialReturn::class, 'id')],
            'remarks' => 'required|string|max:255'
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
            $item = UsedMaterialReturn::where('id', $data->id)->first();
            if (!$item->need_approval) {
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'This data doesn\'t require approval/rejection at this time'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            (new ApprovalService($item, 'used-material-return'))->reject($data->remarks);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $r = ['status' => Response::HTTP_OK, 'result' => 'ok'];
        return response()->json($r, Response::HTTP_OK);
    }

    /**
     * updateStock
     *
     * @param  mixed $details
     * @return void
     */
    private function updateStock($data)
    {
        $project_id = 'dummy-001';
        $po_id = 'dummy-001';

        foreach ($data->details()->get() as $item) {

            $stock = MaterialStock::where(['material_id' => $item->material_id, 'warehouse_id' => $data->warehouse_id, 'project_id' => $project_id])->firstOrNew();
            $stock->material_id = $item->material_id;
            $stock->warehouse_id = $data->warehouse_id;
            $stock->project_id = $project_id;
            $stock->save();
            $stockDetail = MaterialStockDetail::where(['material_stock_id' => $stock->id, 'purchase_order_id' => $po_id])->first();
            if (!$stockDetail) {
                $stockDetail = new MaterialStockDetail();
                $stockDetail->material_stock_id = $stock->id;
                $stockDetail->purchase_order_id = $po_id;
                $stockDetail->bad_stock = (float)$item->qty;
            } else {
                $stockDetail->increment('bad_stock', (float)$item->qty);
            }
            $stockDetail->save();

            $materialHistory = new MaterialStockHistory();
            $materialHistory->material_id = $item->material_id;
            $materialHistory->warehouse_id = $data->warehouse_id;
            $materialHistory->project_id = $project_id;
            $materialHistory->source_type = 'used-material-returns';
            $materialHistory->source_id = $data->id;
            $materialHistory->bad_qty = $item->qty;
            $materialHistory->source_number = $data->number;
            $materialHistory->transaction_date = $data->closed_date;
            $materialHistory->save();
        }
    }
}
