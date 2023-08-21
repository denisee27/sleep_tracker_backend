<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\MaterialDiscrepancy;
use App\Models\MaterialDiscrepancyDetail;
use App\Models\MaterialDisposal;
use App\Models\MaterialStock;
use App\Models\MaterialStockDetail;
use App\Models\MaterialStockHistory;
use App\Models\MaterialToSite;
use App\Models\SupplierInbound;
use App\Models\Transfer;
use App\Services\ApprovalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MaterialDiscrepancyController extends Controller
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
        $items = MaterialDiscrepancy::query();

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
            'creator:id,name',
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
                        ->orWhereHas('details', function ($query) use ($q) {
                            $query->whereHas('material', function ($query) use ($q) {
                                $query->where('number', 'like', '%' . $q . '%')
                                    ->orWhere('name', 'like', '%' . $q . '%');
                            })->orWhereHas('material_stock_detail', function ($query) use ($q) {
                                $query->whereHas('material_stock', function ($query) use ($q) {
                                    $query->whereHas('material', function ($query) use ($q) {
                                        $query->where('number', 'like', '%' . $q . '%')
                                            ->orWhere('number', 'like', '%' . $q . '%');
                                    });
                                });
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
                'details.material_stock_detail:id,material_stock_id,purchase_order_id,good_stock,bad_stock,lost_stock',
                'details.material_stock_detail.purchase_order:id,number,delivery_date',
                'details.material_stock_detail.material_stock:id,project_id',
                'details.material_stock_detail.material_stock.project:id,code',
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
        $r = ['status' => Response::HTTP_OK, 'result' => $data, 'e' => $id];
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
        $reqs = $request->all();
        $reqs['items'] = json_decode($request->items, true);
        $validator = Validator::make($reqs, [
            'id' => ['required', 'string', Rule::exists(MaterialDiscrepancy::class, 'id')],
            'items' => 'required|array',
            'items.*.id' => ['required', 'string', Rule::exists(MaterialDiscrepancyDetail::class, 'id')->where('material_discrepancy_id', $reqs['id'])],
            'items.*.closed_good_qty' => 'nullable|numeric|min:0',
            'items.*.closed_bad_qty' => 'nullable|numeric|min:0',
            'items.*.closed_lost_qty' => 'nullable|numeric|min:0'
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
            $item = MaterialDiscrepancy::find($data->id);
            $item->is_corrected = 1;
            if ($item->status == -1) {
                $approvals = new ApprovalService($item, 'material-discrepancy');
                $approvals->remove();
                $item->status = 0;
                $approvals->createApproval();
            }
            $item->save();

            foreach ($data->items as $_d) {
                $detail = (object)$_d;
                $mDetail = MaterialDiscrepancyDetail::findOrFail($detail->id);
                $mDetail->closed_good_qty = $detail->closed_good_qty;
                $mDetail->closed_bad_qty = $detail->closed_bad_qty;
                $mDetail->closed_lost_qty = $detail->closed_lost_qty;
                $mDetail->save();
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
     * approve
     *
     * @param  mixed $request
     * @return void
     */
    public function approve(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'string', Rule::exists(MaterialDiscrepancy::class, 'id')]
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
            $item = MaterialDiscrepancy::where('id', $data->id)->first();
            if (!$item->need_approval) {
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'This data doesn\'t require approval/rejection at this time'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            (new ApprovalService($item, 'material-discrepancy'))->approve();

            if ($item->status == 1) {
                $item->closed_date = Carbon::now();
                $item->save();
                if (!$item->is_corrected) {
                    DB::unprepared("UPDATE `material_discrepancy_details` SET
                                            `closed_good_qty`=`good_qty`,
                                            `closed_bad_qty`=`bad_qty`,
                                            `closed_lost_qty`=`lost_qty`
                                    WHERE `material_discrepancy_id`='" . $item->id . "'; ");
                }
                $this->updateStock($item);
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
        $reqs = $request->all();
        $reqs['items'] = json_decode($request->items, true);
        $validator = Validator::make($reqs, [
            'id' => ['required', 'string', Rule::exists(MaterialDiscrepancy::class, 'id')],
            'remarks' => 'required|string|max:255',
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
            $item = MaterialDiscrepancy::where('id', $data->id)->with(['details'])->first();
            if (!$item->need_approval) {
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'This data doesn\'t require approval/rejection at this time'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            (new ApprovalService($item, 'material-discrepancy'))->reject($data->remarks);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

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
        foreach (MaterialDisposal::whereIn('id', $ids)->get() as $d) {
            (new ApprovalService($d, 'material-discrepancy'))->remove([$d->id], true);
            $d->delete();
        }
        return $this->index($request);
    }

    /**
     * updateStock
     *
     * @param  mixed $details
     * @return void
     */
    private function updateStock($data)
    {
        $warehouse_id = null;
        if ($data->source_type == 'supplier-inbound') {
            $d = SupplierInbound::findOrFail($data->source_id);
            $warehouse_id = $d->warehouse_id;
        } elseif ($data->source_type == 'transfer-material') {
            $d = Transfer::findOrFail($data->source_id);
            $warehouse_id = $d->to_warehouse;
        } elseif ($data->source_type == 'material-to-site') {
            $d = MaterialToSite::findOrFail($data->source_id);
            $warehouse_id = $d->from_warehouse;
        }
        foreach ($data->details()->get() as $item) {
            $project_id = null;
            $po_id = null;

            if ($data->source_type == 'supplier-inbound') {
                $project_id = $d->purchase_order->project_id;
                $po_id = $d->purchase_order_id;
            } else {
                $project_id = $item->material_stock_detail->material_stock->project_id;
                $po_id = $item->material_stock_detail->purchase_order_id;
            }

            $stock = MaterialStock::where(['material_id' => $item->material_id, 'warehouse_id' => $warehouse_id, 'project_id' => $project_id])->firstOrNew();
            $stock->material_id = $item->material_id;
            $stock->warehouse_id = $warehouse_id;
            $stock->project_id = $project_id;
            $stock->save();

            $stockDetail = MaterialStockDetail::where(['material_stock_id' => $stock->id, 'purchase_order_id' => $po_id])->first();
            if (!$stockDetail) {
                $stockDetail = new MaterialStockDetail();
                $stockDetail->material_stock_id = $stock->id;
                $stockDetail->purchase_order_id = $po_id;
                $stockDetail->good_stock = (float)$item->closed_good_qty;
                $stockDetail->bad_stock = (float)$item->closed_bad_qty;
                $stockDetail->lost_stock = (float)$item->closed_lost_qty;
            } else {
                $stockDetail->increment('good_stock', (float)$item->closed_good_qty);
                $stockDetail->increment('bad_stock', (float)$item->closed_bad_qty);
                $stockDetail->increment('lost_stock', (float) $item->closed_lost_qty);
            }
            $stockDetail->save();

            $materialHistory = new MaterialStockHistory();
            $materialHistory->material_id = $item->material_id;
            $materialHistory->warehouse_id = $warehouse_id;
            $materialHistory->project_id = $project_id;
            $materialHistory->source_type = $data->source_type;
            $materialHistory->source_id = $data->source_id;
            $materialHistory->source_number = $data->source_number;
            $materialHistory->transaction_date = $data->trx_date;
            $materialHistory->good_qty = $item->closed_good_qty;
            $materialHistory->bad_qty = $item->closed_bad_qty;
            $materialHistory->lost_qty = $item->closed_lost_qty;
            $materialHistory->save();
        }
    }
}
