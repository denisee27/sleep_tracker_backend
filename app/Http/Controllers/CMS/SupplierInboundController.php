<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialDiscrepancy;
use App\Models\MaterialDiscrepancyDetail;
use App\Models\MaterialStock;
use App\Models\MaterialStockDetail;
use App\Models\MaterialStockHistory;
use App\Models\PurchaseOrder;
use App\Models\SupplierInbound;
use App\Models\SupplierInboundDetail;
use App\Models\Warehouse;
use App\Services\ApprovalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SupplierInboundController extends Controller
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
        $items = SupplierInbound::query();

        if (!isset($request->forceView)) {
            $items->when(!auth()->user()->is_superadmin, function ($q) {
                $q->where(function ($q) {
                    $q->where('created_by', auth()->user()->id)
                        ->orWhereHas('approvals', function ($q) {
                            $q->where(function ($q) {
                                $q->where('job_position_id', auth()->user()->job_position_id)
                                    ->orWhereRaw("JSON_CONTAINS(`another_job_positions`, '\"" . auth()->user()->job_position_id . "\"')");
                            });
                        });
                });
            });
        }

        $items->orderBy('number', 'desc');
        $items->with([
            'purchase_order:id,supplier_id,company_id,project_id,number,po_date,delivery_date,notes',
            'purchase_order.supplier:id,name',
            'purchase_order.company:id,code,name',
            'purchase_order.project:id,code,name',
            'warehouse:id,code,name',
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

            if (!isset($request->view_partial)) {
                $items->whereNull('supplier_inbound_id');
            }

            if (isset($request->q) && $request->q) {
                $q = $request->q;
                $items->where(function ($query) use ($q) {
                    $query->orWhere('number', 'like', '%' . $q . '%')
                        ->orWhereHas('purchase_order', function ($query) use ($q) {
                            $query->orWhere('number', 'like', '%' . $q . '%')
                                ->orWhereHas('company', function ($query) use ($q) {
                                    $query->where('code', 'like', '%' . $q . '%')
                                        ->orWhere('name', 'like', '%' . $q . '%');
                                })->orWhereHas('project', function ($query) use ($q) {
                                    $query->where('code', 'like', '%' . $q . '%')
                                        ->orWhere('name', 'like', '%' . $q . '%');
                                })->orWhereHas('supplier', function ($query) use ($q) {
                                    $query->where('code', 'like', '%' . $q . '%')
                                        ->orWhere('name', 'like', '%' . $q . '%');
                                });
                        })->orWhereHas('details', function ($query) use ($q) {
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
                'details.material:id,number,name,uom',
                'creator:id,name,job_position_id',
                'creator.job_position:id,role_id,name',
                'creator.job_position.role:id,name',
                'approvals' => function ($q) {
                    $q->select(['job_position_id', 'type', 'type_id', 'status', 'status_name', 'status_order', 'remarks', 'show_notification', 'another_job_positions', 'updated_by', 'updated_at'])
                        ->with(['job_position:id,role_id,name', 'job_position.role:id,name', 'updater:id,name']);
                },
                'childs' => function ($q) {
                    $q->select(['id', 'supplier_inbound_id', 'inbound_date'])
                        ->where('status', 1)
                        ->with(['details', 'details.material:id,number,name,uom']);
                }
            ]);
            $data['data'] = $items->when(isset($request->transaction_id) && $request->transaction_id, function ($q) use ($id) {
                $q->where('number', $id);
            }, function ($q) use ($id) {
                $q->where('id', $id);
            })->first();
            $data['total'] = 1;
        }
        $r = ['status' => Response::HTTP_OK, 'result' => $data, 'x' => auth()->user()->job_position_id];
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
            'purchase_order_id' => ['required', 'string', Rule::exists(PurchaseOrder::class, 'id')],
            'warehouse_id' => ['required', 'string', Rule::exists(Warehouse::class, 'id')->where('type', 'main')],
            'details' => 'required|array',
            'details.*.material_id' => ['required', 'string', Rule::exists(Material::class, 'id')],
            'details.*.qty' => 'required|numeric|min:1'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();

        $warehouse = Warehouse::findOrFail($data->warehouse_id);
        if ($warehouse->in_stock_opname) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => ['warehouse_id' => ['Stock opname is underway at this warehouse']]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            $getLast = SupplierInbound::whereDate('created_at', Carbon::now()->format('Y-m-d'))
                ->orderBy('created_at', 'DESC')
                ->orderBy('number', 'DESC')
                ->sharedLock()
                ->first();
            $lastNumber = (!$getLast) ? 0 : abs(substr($getLast->number, -3));
            $makeNumber = Carbon::now()->format('ymd') . 'SINB' . sprintf('%03s', $lastNumber + 1);
            $cekNumber = SupplierInbound::where('number', $makeNumber)->count();
            if ($cekNumber > 0) {
                DB::rollBack();
                return response()->json([
                    'status' => Response::HTTP_CONFLICT,
                    'message' => 'Try again'
                ], Response::HTTP_CONFLICT);
            }

            $item = new SupplierInbound();
            $item->purchase_order_id = $data->purchase_order_id;
            $item->warehouse_id = $data->warehouse_id;
            $item->number = $makeNumber;
            $item->created_by = auth()->user()->id;
            $item->save();

            foreach ($data->details as $_d) {
                $detail = (object)$_d;
                $poDetail = new SupplierInboundDetail();
                $poDetail->supplier_inbound_id = $item->id;
                $poDetail->material_id = $detail->material_id;
                $poDetail->qty = $detail->qty;
                $poDetail->save();
            }

            (new ApprovalService($item, 'supplier-inbound'))->createApproval();

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
            'id' => ['required', 'string', Rule::exists(SupplierInbound::class, 'id')],
            'warehouse_id' => ['required', 'string', Rule::exists(Warehouse::class, 'id')->where('type', 'main')],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();

        $warehouse = Warehouse::findOrFail($data->warehouse_id);
        if ($warehouse->in_stock_opname) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => ['warehouse_id' => ['Stock opname is underway at this warehouse']]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            $item = SupplierInbound::where('id', $data->id)
                ->lockForUpdate()
                ->firstOrFail();
            $item->warehouse_id = $data->warehouse_id;
            if ($item->status == -1) {
                $approvalSvc = (new ApprovalService($item, 'supplier-inbound'));
                $approvalSvc->remove();
                $item->status = 0;
                $approvalSvc->createApproval();
            }
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
     * approve
     *
     * @param  mixed $request
     * @return void
     */
    public function approve(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'string', Rule::exists(SupplierInbound::class, 'id')]
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
            $item = SupplierInbound::where('id', $data->id)
                ->lockForUpdate()
                ->first();
            if (!$item->need_approval) {
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'This data doesn\'t require approval/rejection at this time'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            (new ApprovalService($item, 'supplier-inbound'))->approve();

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
            'id' => ['required', 'string', Rule::exists(SupplierInbound::class, 'id')],
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
            $item = SupplierInbound::where('id', $data->id)
                ->lockForUpdate()
                ->first();
            if (!$item->need_approval) {
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'This data doesn\'t require approval/rejection at this time'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            (new ApprovalService($item, 'supplier-inbound'))->reject($data->remarks);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $r = ['status' => Response::HTTP_OK, 'result' => 'ok'];
        return response()->json($r, Response::HTTP_OK);
    }

    /**
     * receive
     *
     * @param  mixed $request
     * @return void
     */
    public function receive(Request $request)
    {
        $reqs = $request->all();
        $reqs['items'] = json_decode($request->items, true);
        $validator = Validator::make($reqs, [
            'id' => ['required', 'string', Rule::exists(SupplierInbound::class, 'id')],
            'items' => 'required|array',
            'items.*.id' => ['required', 'string', Rule::exists(SupplierInboundDetail::class, 'id')->where('supplier_inbound_id', $reqs['id'])],
            'items.*.qty' => 'required|numeric',
            'items.*.good_qty' => 'nullable|numeric|min:0',
            'items.*.bad_qty' => 'nullable|numeric|min:0',
            'items.*.lost_qty' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:128',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();

        $isQtyNotSync = collect($data->items)->filter(function ($_i) {
            $i = (object)$_i;
            return (((float)$i->good_qty) + ((float)$i->bad_qty) + ((float)$i->lost_qty)) > $i->qty;
        });

        if ($isQtyNotSync->count()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => [$isQtyNotSync->first()['id'] ?? 'id' => ['Received quantity can\'t exceed the requested quantity']]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $isPartial = collect($data->items)->filter(function ($_i) {
            $i = (object)$_i;
            return (((float)$i->good_qty) + ((float)$i->bad_qty) + ((float)$i->lost_qty)) != $i->qty;
        })->count() > 0;

        DB::beginTransaction();
        try {
            $item = SupplierInbound::where('id', $data->id)
                ->whereHas('approvals', function ($q) {
                    $q->where('status_name', 'reception')
                        ->where('status', 0)
                        ->where('show_notification', 1);
                })
                ->lockForUpdate()
                ->first();
            if (!$item) {
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'This data doesn\'t require reception at this time'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            if (!$item->need_approval) {
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'This data doesn\'t require approval/rejection at this time'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            (new ApprovalService($item, 'supplier-inbound'))->receive();

            $item->inbound_date = Carbon::now();
            $item->status = 1;
            $item->save();
            foreach ($data->items as $d) {
                $detail = (object)$d;
                $siDetail = SupplierInboundDetail::where('id', $detail->id)->firstOrFail();
                $siDetail->good_qty = ($detail->good_qty ? (float)$detail->good_qty : null);
                $siDetail->bad_qty = ($detail->bad_qty ? (float)$detail->bad_qty : null);
                $siDetail->lost_qty = ($detail->lost_qty ? (float)$detail->lost_qty : null);
                $siDetail->notes = $detail->notes;
                $siDetail->save();
            }

            $this->updateStock($item);

            $this->materialDiscrepancy($item);

            if ($isPartial) {
                $this->createSub($item);
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
     * delete
     *
     * @param  mixed $request
     * @return void
     */
    public function delete(Request $request)
    {
        $ids = json_decode($request->getContent());
        $item = SupplierInbound::whereIn('id', $ids);
        $item->delete();
        (new ApprovalService($item, 'supplier-inbound'))->remove($ids, false);
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
        foreach ($data->details()->get() as $item) {
            if (!$item->good_qty) {
                continue;
            }
            $materialStock = MaterialStock::where(['material_id' => $item->material_id, 'warehouse_id' => $data->warehouse_id, 'project_id' => $data->purchase_order->project_id])->firstOrNew();
            $materialStock->material_id = $item->material_id;
            $materialStock->warehouse_id = $data->warehouse_id;
            $materialStock->project_id = $data->purchase_order->project_id;
            $materialStock->save();

            $materialStockDetail = MaterialStockDetail::where(['purchase_order_id' => $data->purchase_order_id, 'material_stock_id' => $materialStock->id])
                ->lockForUpdate()
                ->first();
            if (!$materialStockDetail) {
                $materialStockDetail = new MaterialStockDetail();
                $materialStockDetail->material_stock_id = $materialStock->id;
                $materialStockDetail->purchase_order_id = $data->purchase_order_id;
                $materialStockDetail->good_stock = (float)$item->good_qty;
            } else {
                $materialStockDetail->increment('good_stock', (float)$item->good_qty);
            }
            $materialStockDetail->save();

            $materialHistory = new MaterialStockHistory();
            $materialHistory->material_id = $item->material_id;
            $materialHistory->warehouse_id = $data->warehouse_id;
            $materialHistory->project_id = $data->purchase_order->project_id;
            $materialHistory->purchase_order_id = $data->purchase_order_id;
            $materialHistory->source_type = 'supplier-inbound';
            $materialHistory->source_id = $data->id;
            $materialHistory->good_qty = $item->good_qty;
            $materialHistory->source_number = $data->number;
            $materialHistory->transaction_date = $data->inbound_date;
            $materialHistory->save();
        }
    }

    /**
     * createSub
     *
     * @param  mixed $item
     * @return void
     */
    private function createSub($item)
    {
        $details = $item->details()->get();
        $approvals = $item->approvals()
            ->where('status_name', 'reception')
            ->get();

        $newSI = $item->replicate();
        $newSI->supplier_inbound_id = $item->supplier_inbound_id ?? $item->id;
        $newSI->inbound_date = null;
        $newSI->created_by = auth()->user()->id;
        $newSI->status = 0;
        $newSI->save();

        foreach ($details as $detail) {
            $received_qty = ((float)$detail->good_qty) + ((float)$detail->bad_qty) + ((float)$detail->lost_qty);
            if ($detail->qty == $received_qty) {
                continue;
            }
            $newSID = $detail->replicate();
            $newSID->supplier_inbound_id = $newSI->id;
            $newSID->qty = $detail->qty - $received_qty;
            $newSID->good_qty = null;
            $newSID->bad_qty = null;
            $newSID->lost_qty = null;
            $newSID->notes = null;
            $newSID->save();
        }

        foreach ($approvals as $i => $approval) {
            $newApproval = $approval->replicate();
            $newApproval->type_id = $newSI->id;
            $newApproval->status = 0;
            $newApproval->status_order = ($i + 1);
            $newApproval->show_notification = ($i == 0) ? 1 : 0;
            $newApproval->save();
        }
    }

    /**
     * materialDiscrepancy
     *
     * @param  mixed $item
     * @return void
     */
    private function materialDiscrepancy($item)
    {
        $hasDiff = $item->details()->get()->sum(function ($i) {
            return $i->bad_qty + $i->lost_qty;
        });
        if (!$hasDiff) {
            return;
        }

        $getLast = MaterialDiscrepancy::whereDate('created_at', Carbon::now()->format('Y-m-d'))
            ->orderBy('created_at', 'DESC')
            ->orderBy('number', 'DESC')
            ->sharedLock()
            ->first();
        $lastNumber = (!$getLast) ? 0 : abs(substr($getLast->number, -3));
        $makeNumber = Carbon::now()->format('ymd') . 'MDIS' . sprintf('%03s', $lastNumber + 1);
        $cekNumber = MaterialDiscrepancy::where('number', $makeNumber)->count();
        if ($cekNumber > 0) {
            DB::rollBack();
            return response()->json([
                'status' => Response::HTTP_CONFLICT,
                'message' => 'Try again'
            ], Response::HTTP_CONFLICT);
        }

        $matDiscr = new MaterialDiscrepancy();
        $matDiscr->source_type = 'supplier-inbound';
        $matDiscr->source_number = $item->number;
        $matDiscr->source_id = $item->id;
        $matDiscr->number = $makeNumber;
        $matDiscr->trx_date = Carbon::now();
        $matDiscr->created_by = auth()->user()->id;
        $matDiscr->save();

        foreach ($item->details()->get() as $detail) {
            $matDiscrDetail = new MaterialDiscrepancyDetail();
            $matDiscrDetail->material_discrepancy_id = $matDiscr->id;
            $matDiscrDetail->material_id = $detail->material_id;
            $matDiscrDetail->bad_qty = $detail->bad_qty;
            $matDiscrDetail->lost_qty = $detail->lost_qty;
            $matDiscrDetail->notes = $detail->notes;
            $matDiscrDetail->save();
        }
        (new ApprovalService($matDiscr, 'material-discrepancy'))->createApproval();
    }
}
