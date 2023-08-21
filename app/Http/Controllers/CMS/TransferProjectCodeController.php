<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\MaterialStockDetail;
use App\Models\MaterialStockHistory;
use App\Models\Project;
use App\Models\TransferProjectCode;
use App\Models\TransferProjectCodeDetail;
use App\Models\TransferProjectCodeDetailStock;
use App\Models\Warehouse;
use App\Services\ApprovalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TransferProjectCodeController extends Controller
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
        $items = TransferProjectCode::query();

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
            'warehouse:id,code,name',
            'from_project_code:id,code,name',
            'to_project_code:id,code,name',
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
                        ->orWhereHas('warehouse', function ($query) use ($q) {
                            $query->where('code', 'like', '%' . $q . '%')
                                ->orWhere('name', 'like', '%' . $q . '%');
                        })
                        ->orWhereHas('from_project_code', function ($query) use ($q) {
                            $query->where('code', 'like', '%' . $q . '%')
                                ->orWhere('name', 'like', '%' . $q . '%');
                        })->orWhereHas('to_project_code', function ($query) use ($q) {
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
                'details.stocks:id,transfer_project_code_detail_id,material_stock_detail_id,qty',
                'details.stocks.material_stock_detail:id,material_stock_id,purchase_order_id,good_stock,bad_stock,lost_stock,booked_good_stock',
                'details.stocks.material_stock_detail.purchase_order:id,number,delivery_date',
                'details.stocks.material_stock_detail.material_stock:id,project_id',
                'details.stocks.material_stock_detail.material_stock.project:id,code',
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
            'warehouse_type' => 'required|string|in:main,transit,lastmile',
            'warehouse_id' => ['required', 'string', Rule::exists(Warehouse::class, 'id')->where('type', $data['warehouse_type'])],
            'from_project_code' => ['required', 'string', Rule::exists(Project::class, 'id')],
            'to_project_code' => ['required', 'string', Rule::exists(Project::class, 'id')],
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

        $warehouse = Warehouse::findOrFail($data->warehouse_id);
        if ($warehouse->in_stock_opname) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => ['warehouse_id' => ['Stock opname is underway at this warehouse']]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            $getLast = TransferProjectCode::whereDate('created_at', Carbon::now()->format('Y-m-d'))
                ->orderBy('created_at', 'DESC')
                ->orderBy('number', 'DESC')
                ->sharedLock()
                ->first();
            $lastNumber = (!$getLast) ? 0 : abs(substr($getLast->number, -3));
            $makeNumber = Carbon::now()->format('ymd') . 'TFPR' . sprintf('%03s', $lastNumber + 1);
            $cekNumber = TransferProjectCode::where('number', $makeNumber)->count();
            if ($cekNumber > 0) {
                DB::rollBack();
                return response()->json([
                    'status' => Response::HTTP_CONFLICT,
                    'message' => 'Try again'
                ], Response::HTTP_CONFLICT);
            }

            $item = new TransferProjectCode();
            $item->number = $makeNumber;
            $item->warehouse_id = $data->warehouse_id;
            $item->from_project_code = $data->from_project_code;
            $item->to_project_code = $data->to_project_code;
            $item->warehouse_type = $data->warehouse_type;
            $item->transfer_date = $data->transfer_date;
            $item->notes = $data->notes;
            $item->created_by = auth()->user()->id;
            $item->save();

            foreach ($data->details as $_d) {
                $detail = (object)$_d;
                $tfDetail = new TransferProjectCodeDetail();
                $tfDetail->transfer_project_code_id = $item->id;
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

                    $tfStock = new TransferProjectCodeDetailStock();
                    $tfStock->transfer_project_code_detail_id = $tfDetail->id;
                    $tfStock->material_stock_detail_id = $stock->material_stock_detail_id;
                    $tfStock->qty = $stock->qty;
                    $tfStock->save();
                    $checkStock->increment('booked_good_stock', $tfStock->qty);
                    $checkStock->save();
                }
            }

            (new ApprovalService($item, 'transfer-project-code'))->createApproval();

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
            'id' => ['required', 'string', Rule::exists(TransferProjectCode::class, 'id')],
            'warehouse_type' => 'required|string|in:main,transit,lastmile',
            'warehouse_id' => ['required', 'string', Rule::exists(Warehouse::class, 'id')->where('type', $data['warehouse_type'])],
            'from_project_code' => ['required', 'string', Rule::exists(Project::class, 'id')],
            'to_project_code' => ['required', 'string', Rule::exists(Project::class, 'id')],
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

        $warehouse = Warehouse::findOrFail($data->warehouse_id);
        if ($warehouse->in_stock_opname) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => ['warehouse_id' => ['Stock opname is underway at this warehouse']]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            $item = TransferProjectCode::where('id', $data->id)->firstOrFail();
            $item->from_project_code = $data->from_project_code;
            $item->to_project_code = $data->to_project_code;
            $item->warehouse_type = $data->warehouse_type;
            $item->warehouse_id = $data->warehouse_id;
            $item->transfer_date = $data->transfer_date;
            $item->notes = $data->notes;
            $item->updated_by = auth()->user()->id;

            if ($item->status == -1) {
                $approvals = new ApprovalService($item, 'transfer-project-code');
                $approvals->remove();
                $item->status = 0;
                $approvals->createApproval();
            }

            $item->save();
            $item->details()->whereNotIn('material_id', collect($data->details ?? [])->pluck('material_id'))->forceDelete();

            foreach ($data->details as $_d) {
                $detail = (object)$_d;
                $tfDetail = TransferProjectCodeDetail::firstOrNew(['transfer_project_code_id' => $item->id, 'material_id' => $detail->material_id]);
                $tfDetail->transfer_project_code_id = $item->id;
                $tfDetail->material_id = $detail->material_id;
                $tfDetail->qty = $detail->qty;
                $tfDetail->save();
                $tfDetail->stocks()->whereNotIn('material_stock_detail_id', collect($detail->stocks ?? [])->pluck('material_stock_detail_id'))->forceDelete();

                foreach ($detail->stocks as $_s) {
                    $stock = (object) $_s;
                    $tfStock = TransferProjectCodeDetailStock::firstOrNew(['transfer_project_code_detail_id' => $tfDetail->id, 'material_stock_detail_id' => $stock->material_stock_detail_id]);

                    $checkStock = MaterialStockDetail::findOrFail($stock->material_stock_detail_id);
                    if (($checkStock->good_stock - ($checkStock->booked_good_stock - $tfStock->qty)) < $stock->qty) {
                        DB::rollBack();
                        return response()->json([
                            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                            'message' => 'Stock of Material : ' . $checkStock->material_stock->material->name . ', Project Code : ' . $checkStock->material_stock->project->code . ', PO Number : ' . $checkStock->purchase_order->number . '  is being used in another process as much as ' . ($checkStock->booked_good_stock),
                            'material_id' => $checkStock->material_id
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }

                    $tfStock->transfer_project_code_detail_id = $tfDetail->id;
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
            'id' => ['required', 'string', Rule::exists(TransferProjectCode::class, 'id')]
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
            $item = TransferProjectCode::where('id', $data->id)->first();
            if (!$item->need_approval) {
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'This data doesn\'t require approval/rejection at this time'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            (new ApprovalService($item, 'transfer-project-code'))->approve();

            if ($item->status == 1) {
                $item->received_date = Carbon::now();
                $item->save();
                foreach ($item->details()->get() as $detail) {
                    $this->updateStock($detail);
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
     * reject
     *
     * @param  mixed $request
     * @return void
     */
    public function reject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'string', Rule::exists(TransferProjectCode::class, 'id')],
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
            $item = TransferProjectCode::where('id', $data->id)->with(['details', 'details.stocks'])->first();
            if (!$item->need_approval) {
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'This data doesn\'t require approval/rejection at this time'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            (new ApprovalService($item, 'transfer-project-code'))->reject($data->remarks);

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
     * delete
     *
     * @param  mixed $request
     * @return void
     */
    public function delete(Request $request)
    {
        $ids = json_decode($request->getContent());
        foreach (TransferProjectCode::whereIn('id', $ids)->with(['details', 'details.stocks'])->get() as $d) {
            (new ApprovalService($d, 'transfer-project-code'))->remove([$d->id], false);
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

            $addStock = MaterialStock::where(['material_id' => $item->transfer_project_code_detail->material_id, 'warehouse_id' => $data->transfer_project_code->warehouse_id, 'project_id' => $data->transfer_project_code->to_project_code])->firstOrNew();
            $addStock->material_id = $item->transfer_project_code_detail->material_id;
            $addStock->warehouse_id = $data->transfer_project_code->warehouse_id;
            $addStock->project_id = $data->transfer_project_code->to_project_code;
            $addStock->save();
            $addStockDetail = MaterialStockDetail::where(['material_stock_id' => $addStock->id, 'purchase_order_id' => $item->material_stock_detail->purchase_order_id])->first();
            if (!$addStockDetail) {
                $addStockDetail = new MaterialStockDetail();
                $addStockDetail->material_stock_id = $addStock->id;
                $addStockDetail->purchase_order_id = $item->material_stock_detail->purchase_order_id;
                $addStockDetail->good_stock = (float)$item->qty;
            } else {
                $addStockDetail->increment('good_stock', (float)$item->qty);
            }
            $addStockDetail->save();

            $reduceStock = MaterialStockDetail::where('purchase_order_id', $item->material_stock_detail->purchase_order_id)
                ->whereHas('material_stock', function ($q) use ($item, $data) {
                    $q->where('material_id', $item->transfer_project_code_detail->material_id)
                        ->where('warehouse_id', $data->transfer_project_code->warehouse_id)
                        ->where('project_id', $data->transfer_project_code->from_project_code);
                })->firstOrFail();
            $reduceStock->decrement('good_stock', $item->qty);
            $reduceStock->decrement('booked_good_stock', $item->qty);
            $reduceStock->save();

            $materialHistory = new MaterialStockHistory();
            $materialHistory->material_id = $item->transfer_project_code_detail->material_id;
            $materialHistory->warehouse_id = $data->transfer_project_code->warehouse_id;
            $materialHistory->project_id = $data->transfer_project_code->to_project_code;
            $materialHistory->source_type = 'transfer-project-code';
            $materialHistory->source_id = $data->transfer_project_code_id;
            $materialHistory->good_qty = $item->qty;
            $materialHistory->source_number = $data->transfer_project_code->number;
            $materialHistory->transaction_date = $data->transfer_project_code->received_date;
            $materialHistory->save();

            $materialHistory = new MaterialStockHistory();
            $materialHistory->material_id = $item->transfer_project_code_detail->material_id;
            $materialHistory->warehouse_id = $data->transfer_project_code->warehouse_id;
            $materialHistory->project_id = $data->transfer_project_code->from_project_code;
            $materialHistory->source_type = 'transfer-project-code';
            $materialHistory->source_id = $data->transfer_project_code_id;
            $materialHistory->good_qty = (float) ('-' . $item->good_qty);
            $materialHistory->source_number = $data->transfer_project_code->number;
            $materialHistory->transaction_date = $data->transfer_project_code->received_date;
            $materialHistory->save();
        }
    }
}
