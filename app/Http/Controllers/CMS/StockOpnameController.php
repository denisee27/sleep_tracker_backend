<?php

namespace App\Http\Controllers\CMS;

use App\Exports\StockOpnameTemplateExport;
use App\Helpers\UploadFileHelper;
use App\Http\Controllers\Controller;
use App\Imports\StockOpnameTemplateImport;
use App\Models\MaterialStockDetail;
use App\Models\MaterialStockHistory;
use App\Models\StockOpname;
use App\Models\StockOpnameDetail;
use App\Models\Warehouse;
use App\Services\ApprovalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class StockOpnameController extends Controller
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
        $items = StockOpname::query();

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
                            $query->whereHas('material_stock_detail', function ($query) use ($q) {
                                $query->whereHas('material_stock', function ($query) use ($q) {
                                    $query->whereHas('material', function ($query) use ($q) {
                                        $query->where('number', 'like', '%' . $q . '%')
                                            ->orWhere('name', 'like', '%' . $q . '%');
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
                'details.material_stock_detail',
                'details.material_stock_detail.purchase_order:id,number,delivery_date',
                'details.material_stock_detail.material_stock',
                'details.material_stock_detail.material_stock.material',
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
        $r = ['status' => Response::HTTP_OK, 'result' => $data];
        return response()->json($r, Response::HTTP_OK);
    }


    /**
     * download
     *
     * @param  mixed $id
     * @return void
     */
    public function download($id)
    {
        $item = StockOpname::findOrFail($id);
        return Excel::download(new StockOpnameTemplateExport($id), 'StockOpname-Template-' . $item->number . '.xlsx');
    }

    /**
     * upload
     *
     * @param  mixed $request
     * @return void
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'string', Rule::exists(StockOpname::class, 'id')->where('is_onprocess', 1)],
            'file' => 'required|file|max:5120|mimes:xlsx|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $file = (new UploadFileHelper())->save($request->file('file'));
            Excel::import(new StockOpnameTemplateImport(), public_path('uploads/' . $file));
        } catch (\Throwable $e) {
            unlink(public_path('uploads/' . $file));
            throw $e;
        }
        unlink(public_path('uploads/' . $file));
        $r = ['status' => Response::HTTP_OK, 'result' => 'ok'];
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
            'opname_date' => 'required|date_format:Y-m-d',
            'notes' => 'nullable|string|max:255'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();
        $warehouse = Warehouse::findOrFail($data->warehouse_id);
        if ($warehouse->in_operation) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => ['warehouse_id' => ['Transactions are taking place in this warehouse']]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            $getLast = StockOpname::whereDate('created_at', Carbon::now()->format('Y-m-d'))
                ->orderBy('created_at', 'DESC')
                ->orderBy('number', 'DESC')
                ->sharedLock()
                ->first();
            $lastNumber = (!$getLast) ? 0 : abs(substr($getLast->number, -3));
            $makeNumber = Carbon::now()->format('ymd') . 'STON' . sprintf('%03s', $lastNumber + 1);
            $cekNumber = StockOpname::where('number', $makeNumber)->count();
            if ($cekNumber > 0) {
                DB::rollBack();
                return response()->json([
                    'status' => Response::HTTP_CONFLICT,
                    'message' => 'Try again'
                ], Response::HTTP_CONFLICT);
            }

            $item = new StockOpname();
            $item->number = $makeNumber;
            $item->warehouse_id = $data->warehouse_id;
            $item->warehouse_type = $data->warehouse_type;
            $item->opname_date = $data->opname_date;
            $item->notes = $data->notes;
            $item->created_by = auth()->user()->id;
            $item->save();

            $stocks = MaterialStockDetail::whereHas('material_stock', function ($q) use ($data) {
                $q->where('warehouse_id', $data->warehouse_id);
            })->get();

            if (!count($stocks)) {
                DB::rollBack();
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'wrong' => ['warehouse_id' => ['There\'s no material available in this warehouse']]
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            foreach ($stocks as $stock) {
                $soDetail = new StockOpnameDetail();
                $soDetail->stock_opname_id = $item->id;
                $soDetail->material_stock_detail_id = $stock->id;
                $soDetail->system_good_qty = $stock->good_stock;
                $soDetail->system_bad_qty = $stock->bad_stock;
                $soDetail->system_lost_qty = $stock->lost_stock;
                $soDetail->save();
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
     * submit
     *
     * @param  mixed $request
     * @return void
     */
    public function submit(Request $request)
    {
        $reqs = $request->all();
        $reqs['details'] = json_decode($request->details, true);
        $validator = Validator::make($reqs, [
            'id' => ['required', 'string', Rule::exists(StockOpname::class, 'id')->where('is_onprocess', 1)],
            'details' => 'required|array',
            'details.*.id' => ['required', 'string', Rule::exists(StockOpnameDetail::class, 'id')],
            'details.*.counted_good_qty' => 'required|numeric|min:0',
            'details.*.counted_bad_qty' => 'required|numeric|min:0',
            'details.*.counted_lost_qty' => 'required|numeric|min:0',
            'details.*.notes' => 'nullable|string|max:255',
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
            $item = StockOpname::where('id', $data->id)->firstOrFail();
            $item->is_onprocess = 0;
            $item->updated_by = auth()->user()->id;
            $item->save();

            foreach ($data->details as $_s) {
                $stock = (object)$_s;
                $soDetail = StockOpnameDetail::findOrFail($stock->id);
                $soDetail->counted_good_qty = $stock->counted_good_qty;
                $soDetail->counted_bad_qty = $stock->counted_bad_qty;
                $soDetail->counted_lost_qty = $stock->counted_lost_qty;
                $soDetail->notes = $stock->notes;
                $soDetail->save();
            }

            (new ApprovalService($item, 'stock-opname'))->createApproval();

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
            'id' => ['required', 'string', Rule::exists(StockOpname::class, 'id')],
            'warehouse_type' => 'required|string|in:main,transit,lastmile',
            'warehouse_id' => ['required', 'string', Rule::exists(Warehouse::class, 'id')->where('type', $data['warehouse_type'])],
            'opname_date' => 'required|date_format:Y-m-d',
            'notes' => 'nullable|string|max:255',
            'is_onprocess' => 'required|numeric|in:0,1',
            'details' => 'required_if:is_onprocess,0|array',
            'details.*.id' => ['required_if:is_onprocess,0', 'string', Rule::exists(StockOpnameDetail::class, 'id')],
            'details.*.counted_good_qty' => 'required_if:is_onprocess,0|numeric|min:0',
            'details.*.counted_bad_qty' => 'required_if:is_onprocess,0|numeric|min:0',
            'details.*.counted_lost_qty' => 'required_if:is_onprocess,0|numeric|min:0',
            'details.*.notes' => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();

        $warehouse = Warehouse::findOrFail($data->warehouse_id);
        if ($warehouse->in_operation) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => ['warehouse_id' => ['Transactions are taking place in this warehouse']]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            $item = StockOpname::where('id', $data->id)->firstOrFail();
            if ($data->warehouse_id != $item->warehouse_id) {
                $item->is_onprocess = 1;
                $item->details()->forceDelete();
                $stocks = MaterialStockDetail::whereHas('material_stock', function ($q) use ($data) {
                    $q->where('warehouse_id', $data->warehouse_id);
                })->get();

                if (!count($stocks)) {
                    DB::rollBack();
                    return response()->json([
                        'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                        'wrong' => ['warehouse_id' => ['There\'s no material available in this warehouse']]
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                foreach ($stocks as $stock) {
                    $soDetail = new StockOpnameDetail();
                    $soDetail->stock_opname_id = $item->id;
                    $soDetail->material_stock_detail_id = $stock->id;
                    $soDetail->system_good_qty = $stock->good_stock;
                    $soDetail->system_bad_qty = $stock->bad_stock;
                    $soDetail->system_lost_qty = $stock->lost_stock;
                    $soDetail->save();
                }

                $approvals = new ApprovalService($item, 'stock-opname');
                $approvals->remove();
            } elseif (!$data->is_onprocess) {
                foreach ($data->details as $_s) {
                    $stock = (object)$_s;
                    $soDetail = StockOpnameDetail::findOrFail($stock->id);
                    $soDetail->counted_good_qty = $stock->counted_good_qty;
                    $soDetail->counted_bad_qty = $stock->counted_bad_qty;
                    $soDetail->counted_lost_qty = $stock->counted_lost_qty;
                    $soDetail->notes = $stock->notes;
                    $soDetail->save();
                }
            }
            $item->warehouse_type = $data->warehouse_type;
            $item->warehouse_id = $data->warehouse_id;
            $item->opname_date = $data->opname_date;
            $item->notes = $data->notes;
            $item->updated_by = auth()->user()->id;

            if ($item->status == -1) {
                $approvals = new ApprovalService($item, 'stock-opname');
                $approvals->remove();
                $item->status = 0;
                $approvals->createApproval();
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
            'id' => ['required', 'string', Rule::exists(StockOpname::class, 'id')]
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
            $item = StockOpname::where('id', $data->id)->first();
            if (!$item->need_approval) {
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'This data doesn\'t require approval/rejection at this time'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            (new ApprovalService($item, 'stock-opname'))->approve();

            if ($item->status == 1) {
                $item->closed_date = Carbon::now();
                $item->save();
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
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'string', Rule::exists(StockOpname::class, 'id')],
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
            $item = StockOpname::where('id', $data->id)->with(['details'])->first();
            if (!$item->need_approval) {
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'This data doesn\'t require approval/rejection at this time'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            (new ApprovalService($item, 'stock-opname'))->reject($data->remarks);

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
        foreach (StockOpname::whereIn('id', $ids)->get() as $d) {
            (new ApprovalService($d, 'stock-opname'))->remove([$d->id], false);
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
        foreach ($data->details()->get() as $item) {

            $stockDetail = MaterialStockDetail::where('id', $item->material_stock_detail_id)->firstOrFail();
            $old_good = $item->good_stock;
            $old_bad = $item->bad_stock;
            $old_lost = $item->lost_stock;
            $stockDetail->decrement('good_stock', ($item->system_good_qty - $item->counted_good_qty));
            $stockDetail->decrement('bad_stock', ($item->system_bad_qty - $item->counted_bad_qty));
            $stockDetail->decrement('lost_stock', ($item->system_lost_qty - $item->counted_lost_qty));
            $stockDetail->save();

            if ($stockDetail->good_stock != $old_good) {
                $materialHistory = new MaterialStockHistory();
                $materialHistory->material_id = $stockDetail->material_stock->material_id;
                $materialHistory->warehouse_id = $data->warehouse_id;
                $materialHistory->project_id = $stockDetail->material_stock->project_id;
                $materialHistory->source_type = 'stock-opname';
                $materialHistory->source_id = $data->id;
                $materialHistory->good_qty = ($item->system_good_qty - $item->counted_good_qty);
                $materialHistory->source_number = $data->number;
                $materialHistory->transaction_date = $data->closed_date;
                $materialHistory->save();
            }

            if ($stockDetail->bad_stock != $old_bad) {
                $materialHistory = new MaterialStockHistory();
                $materialHistory->material_id = $stockDetail->material_stock->material_id;
                $materialHistory->warehouse_id = $data->warehouse_id;
                $materialHistory->project_id = $stockDetail->material_stock->project_id;
                $materialHistory->source_type = 'stock-opname';
                $materialHistory->source_id = $data->id;
                $materialHistory->bad_qty = ($item->system_bad_qty - $item->counted_bad_qty);
                $materialHistory->source_number = $data->number;
                $materialHistory->transaction_date = $data->closed_date;
                $materialHistory->save();
            }

            if ($stockDetail->lost_stock != $old_lost) {
                $materialHistory = new MaterialStockHistory();
                $materialHistory->material_id = $stockDetail->material_stock->material_id;
                $materialHistory->warehouse_id = $data->warehouse_id;
                $materialHistory->project_id = $stockDetail->material_stock->project_id;
                $materialHistory->source_type = 'stock-opname';
                $materialHistory->source_id = $data->id;
                $materialHistory->bad_qty = ($item->system_lost_qty - $item->counted_lost_qty);
                $materialHistory->source_number = $data->number;
                $materialHistory->transaction_date = $data->closed_date;
                $materialHistory->save();
            }
        }
    }
}
