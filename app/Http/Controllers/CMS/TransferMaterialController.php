<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialDiscrepancy;
use App\Models\MaterialDiscrepancyDetail;
use App\Models\MaterialStock;
use App\Models\MaterialStockDetail;
use App\Models\MaterialStockHistory;
use App\Models\Transfer;
use App\Models\TransferDetail;
use App\Models\TransferDetailStock;
use App\Models\Warehouse;
use App\Services\ApprovalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TransferMaterialController extends Controller
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
        $items = Transfer::query();

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
            'from_warehouse:id,code,name',
            'to_warehouse:id,code,name',
            'creator:id,name',
            'approvals' => function ($q) {
                $q->select(['job_position_id', 'type', 'type_id', 'status', 'status_name', 'status_order'])
                    ->where('status', 0)
                    ->where('show_notification', 1)
                    ->with([
                        'job_position:id,role_id',
                        'job_position.role:id,name'
                    ]);
            }
        ]);

        if (isset($request->filter) && $request->filter) {
            $filter = json_decode($request->filter, true);
            $items->where($filter);
        }

        if ($id == null) {

            if (!isset($request->view_partial)) {
                $items->whereNull('transfer_id');
            }

            if (isset($request->q) && $request->q) {
                $q = $request->q;
                $items->where(function ($query) use ($q) {
                    $query->orWhere('number', 'like', '%' . $q . '%')
                        ->orWhereHas('from_warehouse', function ($query) use ($q) {
                            $query->where('code', 'like', '%' . $q . '%')
                                ->orWhere('name', 'like', '%' . $q . '%');
                        })->orWhereHas('to_warehouse', function ($query) use ($q) {
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
                'details.material:id,category_id,number,name,uom,is_fifo',
                'details.material.category:id,name',
                'details.stocks:id,transfer_detail_id,material_stock_detail_id,qty,good_qty,bad_qty,lost_qty,notes',
                'details.stocks.material_stock_detail:id,material_stock_id,purchase_order_id,good_stock,bad_stock,lost_stock,booked_good_stock',
                'details.stocks.material_stock_detail.purchase_order:id,number,delivery_date',
                'details.stocks.material_stock_detail.material_stock:id,project_id',
                'details.stocks.material_stock_detail.material_stock.project:id,code',
                'creator:id,name,job_position_id',
                'creator.job_position:id,role_id,name',
                'creator.job_position.role:id,name',
                'approvals' => function ($q) {
                    $q->select(['job_position_id', 'type', 'type_id', 'status', 'status_name', 'status_order', 'remarks', 'show_notification', 'another_job_positions', 'updated_by', 'updated_at'])
                        ->with(['job_position:id,role_id,name', 'job_position.role:id,name', 'updater:id,name']);
                },
                'childs' => function ($q) {
                    $q->select(['id', 'transfer_id', 'transfer_date', 'received_date'])
                        ->where('status', 1)
                        ->with([
                            'details',
                            'details.material:id,number,name,uom',
                            'details.stocks:id,transfer_detail_id,material_stock_detail_id,qty,good_qty,bad_qty,lost_qty,notes',
                            'details.stocks.material_stock_detail:id,material_stock_id,purchase_order_id,good_stock,bad_stock,lost_stock',
                            'details.stocks.material_stock_detail.purchase_order:id,number,delivery_date',
                            'details.stocks.material_stock_detail.material_stock:id,project_id',
                            'details.stocks.material_stock_detail.material_stock.project:id,code',
                        ]);
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
            'from_type' => 'required|string|in:main,transit,lastmile',
            'to_type' => 'required|string|in:main,transit,lastmile',
            'from_warehouse' => ['required', 'string', Rule::exists(Warehouse::class, 'id')->where('type', $data['from_type'])],
            'to_warehouse' => ['required', 'string', Rule::exists(Warehouse::class, 'id')->where('type', $data['to_type'])],
            'transfer_date' => 'required|date_format:Y-m-d',
            'notes' => 'nullable|string|max:255',
            'details' => 'required|array',
            'details.*.material_id' => ['required', 'string', Rule::exists(Material::class, 'id')],
            'details.*.qty' => 'required|numeric|min:1',
            'details.*.stocks' => 'required|array',
            'details.*.stocks.*.material_stock_detail_id' => ['required', 'string', Rule::exists(MaterialStockDetail::class, 'id')],
            'details.*.stocks.*.qty' => 'required|numeric|min:1',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();

        $from_warehouse = Warehouse::findOrFail($data->from_warehouse);
        if ($from_warehouse->in_stock_opname) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => ['from_warehouse' => ['Stock opname is underway at this warehouse']]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $to_warehouse = Warehouse::findOrFail($data->to_warehouse);
        if ($to_warehouse->in_stock_opname) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => ['to_warehouse' => ['Stock opname is underway at this warehouse']]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            $getLast = Transfer::whereDate('created_at', Carbon::now()->format('Y-m-d'))
                ->orderBy('created_at', 'DESC')
                ->orderBy('number', 'DESC')
                ->sharedLock()
                ->first();
            $lastNumber = (!$getLast) ? 0 : abs(substr($getLast->number, -3));
            $makeNumber = Carbon::now()->format('ymd') . 'OTIN' . sprintf('%03s', $lastNumber + 1);
            $cekNumber = Transfer::where('number', $makeNumber)->count();
            if ($cekNumber > 0) {
                DB::rollBack();
                return response()->json([
                    'status' => Response::HTTP_CONFLICT,
                    'message' => 'Try again'
                ], Response::HTTP_CONFLICT);
            }

            $item = new Transfer();
            $item->number = $makeNumber;
            $item->from_warehouse = $data->from_warehouse;
            $item->to_warehouse = $data->to_warehouse;
            $item->from_type = $data->from_type;
            $item->to_type = $data->to_type;
            $item->transfer_date = $data->transfer_date;
            $item->notes = $data->notes;
            $item->created_by = auth()->user()->id;
            $item->save();

            foreach ($data->details as $_d) {
                $detail = (object)$_d;
                $tfDetail = new TransferDetail();
                $tfDetail->transfer_id = $item->id;
                $tfDetail->material_id = $detail->material_id;
                $tfDetail->qty = $detail->qty;
                $tfDetail->save();

                foreach ($detail->stocks as $_s) {
                    $stock = (object) $_s;
                    $checkStock = MaterialStockDetail::findOrFail($stock->material_stock_detail_id);
                    if (($checkStock->good_stock - $checkStock->booked_good_stock) < $stock->qty) {
                        DB::rollBack();
                        return response()->json([
                            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                            'message' => 'Stock of Material : ' . $checkStock->material_stock->material->name . ', Project Code : ' . $checkStock->material_stock->project->code . ', PO Number : ' . $checkStock->purchase_order->number . '  is being used in another process as much as ' . ($checkStock->booked_good_stock),
                            'material_id' => $checkStock->material_id
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }

                    $tfStock = new TransferDetailStock();
                    $tfStock->transfer_detail_id = $tfDetail->id;
                    $tfStock->material_stock_detail_id = $stock->material_stock_detail_id;
                    $tfStock->qty = $stock->qty;
                    $tfStock->save();
                    $checkStock->increment('booked_good_stock', $tfStock->qty);
                    $checkStock->save();
                }
            }

            (new ApprovalService($item, 'transfer', $item->from_type, $item->to_type, true))->createApproval();

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
            'id' => ['required', 'string', Rule::exists(Transfer::class, 'id')],
            'from_type' => 'required|string|in:main,transit,lastmile',
            'to_type' => 'required|string|in:main,transit,lastmile',
            'from_warehouse' => ['required', 'string', Rule::exists(Warehouse::class, 'id')->where('type', $data['from_type'])],
            'to_warehouse' => ['required', 'string', Rule::exists(Warehouse::class, 'id')->where('type', $data['to_type'])],
            'transfer_date' => 'required|date_format:Y-m-d',
            'notes' => 'nullable|string|max:255',
            'details' => 'required|array',
            'details.*.material_id' => ['required', 'string', Rule::exists(Material::class, 'id')],
            'details.*.qty' => 'required|numeric|min:1',
            'details.*.stocks' => 'required|array',
            'details.*.stocks.*.material_stock_detail_id' => ['nullable', 'string'],
            'details.*.stocks.*.qty' => 'required|numeric|min:1',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();

        $from_warehouse = Warehouse::findOrFail($data->from_warehouse);
        if ($from_warehouse->in_stock_opname) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => ['from_warehouse' => ['Stock opname is underway at this warehouse']]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $to_warehouse = Warehouse::findOrFail($data->to_warehouse);
        if ($to_warehouse->in_stock_opname) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => ['to_warehouse' => ['Stock opname is underway at this warehouse']]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            $item = Transfer::where('id', $data->id)->firstOrFail();
            $item->from_warehouse = $data->from_warehouse;
            $item->to_warehouse = $data->to_warehouse;
            $item->from_type = $data->from_type;
            $item->to_type = $data->to_type;
            $item->transfer_date = $data->transfer_date;
            $item->notes = $data->notes;
            $item->updated_by = auth()->user()->id;

            if ($item->status == -1) {
                $approvals = new ApprovalService($item, 'transfer', $item->from_type, $item->to_type, true);
                $approvals->remove();
                $item->status = 0;
                $approvals->createApproval();
            }

            $item->save();
            $item->details()->whereNotIn('material_id', collect($data->details ?? [])->pluck('material_id'))->forceDelete();

            foreach ($data->details as $_d) {
                $detail = (object)$_d;
                $tfDetail = TransferDetail::firstOrNew(['transfer_id' => $item->id, 'material_id' => $detail->material_id]);
                $tfDetail->transfer_id = $item->id;
                $tfDetail->material_id = $detail->material_id;
                $tfDetail->qty = $detail->qty;
                $tfDetail->save();
                $tfDetail->stocks()->whereNotIn('material_stock_detail_id', collect($detail->stocks ?? [])->pluck('material_stock_detail_id'))->forceDelete();

                foreach ($detail->stocks as $_s) {
                    $stock = (object) $_s;
                    $tfStock = TransferDetailStock::firstOrNew(['transfer_detail_id' => $tfDetail->id, 'material_stock_detail_id' => $stock->material_stock_detail_id]);

                    $checkStock = MaterialStockDetail::findOrFail($stock->material_stock_detail_id);
                    if (($checkStock->good_stock - ($checkStock->booked_good_stock - $tfStock->qty)) < $stock->qty) {
                        DB::rollBack();
                        return response()->json([
                            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                            'message' => 'Stock of Material : ' . $checkStock->material_stock->material->name . ', Project Code : ' . $checkStock->material_stock->project->code . ', PO Number : ' . $checkStock->purchase_order->number . '  is being used in another process as much as ' . ($checkStock->booked_good_stock),
                            'material_id' => $checkStock->material_id
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }

                    $tfStock->transfer_detail_id = $tfDetail->id;
                    $tfStock->material_stock_detail_id = $stock->material_stock_detail_id;
                    $tfStock->qty = $stock->qty;
                    $tfStock->save();
                    $checkStock->increment('booked_good_stock', ($tfStock->qty - $checkStock->booked_good_stock));
                    $checkStock->save();
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
     * approve
     *
     * @param  mixed $request
     * @return void
     */
    public function approve(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'string', Rule::exists(Transfer::class, 'id')]
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
            $item = Transfer::where('id', $data->id)->first();
            if (!$item->need_approval) {
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'This data doesn\'t require approval/rejection at this time'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            (new ApprovalService($item, 'transfer', $item->from_type, $item->to_type))->approve();

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
            'id' => ['required', 'string', Rule::exists(Transfer::class, 'id')],
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
            $item = Transfer::where('id', $data->id)->with(['details', 'details.stocks'])->first();
            if (!$item->need_approval) {
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'This data doesn\'t require approval/rejection at this time'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            (new ApprovalService($item, 'transfer', $item->from_type, $item->to_type))->reject($data->remarks);

            foreach ($item->details as $detail) {
                foreach ($detail->stocks as $s) {
                    $stock = MaterialStockDetail::findOrFail($s->material_stock_detail_id);
                    $stock->decrement('booked_good_stock', $s->qty);
                    $stock->save();
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
            'id' => ['required', 'string', Rule::exists(Transfer::class, 'id')],
            'items' => 'required|array',
            'items.*.id' => ['required', 'string', Rule::exists(TransferDetail::class, 'id')->where('transfer_id', $reqs['id'])],
            'items.*.stocks' => 'required|array',
            'items.*.stocks.*.id' => 'required|string',
            'items.*.stocks.*.qty' => 'required|numeric',
            'items.*.stocks.*.good_qty' => 'nullable|numeric|min:0',
            'items.*.stocks.*.bad_qty' => 'nullable|numeric|min:0',
            'items.*.stocks.*.lost_qty' => 'nullable|numeric|min:0',
            'items.*.stocks.*.notes' => 'nullable|string|max:128',
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
            return collect($i->stocks)->filter(function ($_x) {
                $x = (object)$_x;
                return (((float)$x->good_qty) + ((float)$x->bad_qty) + ((float)$x->lost_qty)) > $x->qty;
            })->count() > 0;
        });

        if ($isQtyNotSync->count()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => [$isQtyNotSync->first()['stocks'][0]['id'] ?? 'id' => ['Received quantity can\'t exceed the requested quantity']]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $isPartial = collect($data->items)->filter(function ($_i) {
            $i = (object)$_i;
            return collect($i->stocks)->filter(function ($_x) {
                $x = (object)$_x;
                return (((float)$x->good_qty) + ((float)$x->bad_qty) + ((float)$x->lost_qty)) != $x->qty;
            })->count() > 0;
        })->count() > 0;

        DB::beginTransaction();
        try {
            $item = Transfer::where('id', $data->id)
                ->whereHas('approvals', function ($q) {
                    $q->where('status_name', 'reception')
                        ->where('status', 0)
                        ->where('show_notification', 1);
                })->first();
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

            (new ApprovalService($item, 'transfer', $item->from_type, $item->to_type))->receive();

            $item->received_date = Carbon::now();
            $item->status = 1;
            $item->save();
            foreach ($data->items as $d) {
                $detail = (object)$d;
                $tfDetail = TransferDetail::where('id', $detail->id)->firstOrFail();
                foreach ($detail->stocks as $s) {
                    $stock = (object) $s;
                    $dStock = TransferDetailStock::where('id', $stock->id)->firstOrFail();
                    $dStock->good_qty = ($stock->good_qty ? (float)$stock->good_qty : null);
                    $dStock->bad_qty = ($stock->bad_qty ? (float)$stock->bad_qty : null);
                    $dStock->lost_qty = ($stock->lost_qty ? (float)$stock->lost_qty : null);
                    $dStock->notes = $stock->notes;
                    $dStock->save();
                }
                $tfDetail->save();
                $this->updateStock($tfDetail);
            }

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
        foreach (Transfer::whereIn('id', $ids)->with(['details', 'details.stocks'])->get() as $d) {
            (new ApprovalService($d, 'transfer', $d->from_type, $d->to_type))->remove([$d->id], false);
            foreach ($d->details as $detail) {
                foreach ($detail->stocks as $s) {
                    $stock = MaterialStockDetail::findOrFail($s->material_stock_detail_id);
                    $stock->decrement('booked_good_stock', $s->qty);
                    $stock->save();
                }
            }
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
        foreach ($data->stocks()->get() as $item) {
            if (!$item->good_qty) {
                continue;
            }

            $addStock = MaterialStock::where(['material_id' => $item->transfer_detail->material_id, 'warehouse_id' => $data->transfer->to_warehouse, 'project_id' => $item->material_stock_detail->material_stock->project_id])->firstOrNew();
            $addStock->material_id = $item->transfer_detail->material_id;
            $addStock->warehouse_id = $data->transfer->to_warehouse;
            $addStock->project_id = $item->material_stock_detail->material_stock->project_id;
            $addStock->save();
            $addStockDetail = MaterialStockDetail::where(['material_stock_id' => $addStock->id, 'purchase_order_id' => $item->material_stock_detail->purchase_order_id])->first();
            if (!$addStockDetail) {
                $addStockDetail = new MaterialStockDetail();
                $addStockDetail->material_stock_id = $addStock->id;
                $addStockDetail->purchase_order_id = $item->material_stock_detail->purchase_order_id;
                $addStockDetail->good_stock = (float)$item->good_qty;
            } else {
                $addStockDetail->increment('good_stock', (float)$item->good_qty);
            }
            $addStockDetail->save();

            $reduceStock = MaterialStockDetail::where('purchase_order_id', $item->material_stock_detail->purchase_order_id)
                ->whereHas('material_stock', function ($q) use ($item, $data) {
                    $q->where('material_id', $item->transfer_detail->material_id)
                        ->where('warehouse_id', $data->transfer->from_warehouse)
                        ->where('project_id', $item->material_stock_detail->material_stock->project_id);
                })->firstOrFail();
            $reduceStock->decrement('good_stock', ($item->good_qty + $item->bad_qty + $item->lost_qty));
            $reduceStock->decrement('booked_good_stock', ($item->good_qty + $item->bad_qty + $item->lost_qty));
            $reduceStock->save();

            $materialHistory = new MaterialStockHistory();
            $materialHistory->material_id = $item->transfer_detail->material_id;
            $materialHistory->warehouse_id = $data->transfer->from_warehouse;
            $materialHistory->project_id = $item->material_stock_detail->material_stock->project_id;
            $materialHistory->source_type = 'transfer-material';
            $materialHistory->source_id = $data->transfer_id;
            $materialHistory->good_qty = (float) ('-' . $item->good_qty);
            $materialHistory->source_number = $data->transfer->number;
            $materialHistory->transaction_date = $data->transfer->received_date;
            $materialHistory->save();

            $materialHistory = new MaterialStockHistory();
            $materialHistory->material_id = $item->transfer_detail->material_id;
            $materialHistory->warehouse_id = $data->transfer->to_warehouse;
            $materialHistory->project_id = $item->material_stock_detail->material_stock->project_id;
            $materialHistory->source_type = 'transfer-material';
            $materialHistory->source_id = $data->transfer_id;
            $materialHistory->good_qty = $item->good_qty;
            $materialHistory->source_number = $data->transfer->number;
            $materialHistory->transaction_date = $data->transfer->received_date;
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
        $approvals = $item->approvals()
            ->where('status_name', 'reception')
            ->get();

        $newTransfer = $item->replicate();
        $newTransfer->transfer_id = $item->transfer_id ?? $item->id;
        $newTransfer->received_date = null;
        $newTransfer->created_by = auth()->user()->id;
        $newTransfer->status = 0;
        $newTransfer->save();

        $details = TransferDetail::where('transfer_id', $item->id)
            ->withSum('stocks as s_good_qty', 'good_qty')
            ->withSum('stocks as s_bad_qty', 'bad_qty')
            ->withSum('stocks as s_lost_qty', 'lost_qty')
            ->get();
        foreach ($details as $detail) {
            $received_qty = ((float)$detail->s_good_qty) + ((float)$detail->s_bad_qty) + ((float)$detail->s_lost_qty);
            if ($detail->qty == $received_qty) {
                continue;
            }
            unset($detail->s_good_qty, $detail->s_bad_qty, $detail->s_lost_qty);
            $newTfDetail = $detail->replicate();
            $newTfDetail->transfer_id = $newTransfer->id;
            $newTfDetail->qty = $detail->qty - $received_qty;
            $newTfDetail->save();

            foreach ($detail->stocks()->get() as $stock) {
                $newStock = $stock->replicate();
                $newStock->transfer_detail_id = $newTfDetail->id;
                $newStock->qty = $newTfDetail->qty;
                $newStock->good_qty = null;
                $newStock->bad_qty = null;
                $newStock->good_qty = null;
                $newStock->save();
            }
        }

        foreach ($approvals as $i => $approval) {
            $newApproval = $approval->replicate();
            $newApproval->type_id = $newTransfer->id;
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

        $hasDiff = $item->details()->with('stocks')->get()->map(function ($i) {
            $i->summed = $i->stocks->sum(function ($x) {
                return $x->bad_qty + $x->lost_qty;
            });
            return $i;
        })->sum('summed');

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
        $matDiscr->source_type = 'transfer-material';
        $matDiscr->source_number = $item->number;
        $matDiscr->source_id = $item->id;
        $matDiscr->number = $makeNumber;
        $matDiscr->trx_date = Carbon::now();
        $matDiscr->created_by = auth()->user()->id;
        $matDiscr->save();

        foreach ($item->details()->get() as $_d) {
            foreach ($_d->stocks()->get() as $detail) {
                $matDiscrDetail = new MaterialDiscrepancyDetail();
                $matDiscrDetail->material_discrepancy_id = $matDiscr->id;
                $matDiscrDetail->material_id = $detail->material_stock_detail->material_stock->material_id;
                $matDiscrDetail->material_stock_detail_id = $detail->material_stock_detail_id;
                $matDiscrDetail->bad_qty = $detail->bad_qty;
                $matDiscrDetail->lost_qty = $detail->lost_qty;
                $matDiscrDetail->notes = $detail->notes;
                $matDiscrDetail->save();
            }
        }
        (new ApprovalService($matDiscr, 'material-discrepancy'))->createApproval();
    }
}
