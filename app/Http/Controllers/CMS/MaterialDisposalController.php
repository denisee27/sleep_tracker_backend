<?php

namespace App\Http\Controllers\CMS;

use App\Exports\MaterialDisposalItemExport;
use App\Helpers\UploadFileHelper;
use App\Http\Controllers\Controller;
use App\Models\MaterialDisposal;
use App\Models\MaterialDisposalDetail;
use App\Models\MaterialStockDetail;
use App\Models\MaterialStockHistory;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class MaterialDisposalController extends Controller
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
        $items = MaterialDisposal::query();

        if (!isset($request->forceView)) {
            $items->when(!auth()->user()->is_superadmin, function ($q) {
                $q->where('created_by', auth()->user()->id);
            });
        }

        $items->orderBy('number', 'desc');
        $items->with([
            'warehouse:id,code,name',
            'creator:id,job_position_id,name',
            'creator.job_position:id,role_id,name',
            'creator.job_position.role:id,name'
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
                'details.material_stock_detail.purchase_order.details:purchase_order_id,material_id,idr_price',
                'details.material_stock_detail.material_stock',
                'details.material_stock_detail.material_stock.material',
                'details.material_stock_detail.material_stock.project:id,code',
                'creator:id,name,job_position_id',
                'creator.job_position:id,role_id,name',
                'creator.job_position.role:id,name'
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
            'disposal_date' => 'required|date_format:Y-m-d',
            'notes' => 'nullable|string|max:255',
            'details' => 'required|array',
            'details.*.material_stock_detail_id' => ['required', 'string', Rule::exists(MaterialStockDetail::class, 'id')],
            'details.*.system_bad_qty' => 'required|numeric|min:0',
            'details.*.disposed_bad_qty' => 'required|numeric|min:0'
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
            $getLast = MaterialDisposal::whereDate('created_at', Carbon::now()->format('Y-m-d'))
                ->orderBy('created_at', 'DESC')
                ->orderBy('number', 'DESC')
                ->sharedLock()
                ->first();
            $lastNumber = (!$getLast) ? 0 : abs(substr($getLast->number, -3));
            $makeNumber = Carbon::now()->format('ymd') . 'MDSP' . sprintf('%03s', $lastNumber + 1);
            $cekNumber = MaterialDisposal::where('number', $makeNumber)->count();
            if ($cekNumber > 0) {
                DB::rollBack();
                return response()->json([
                    'status' => Response::HTTP_CONFLICT,
                    'message' => 'Try again'
                ], Response::HTTP_CONFLICT);
            }

            $item = new MaterialDisposal();
            $item->number = $makeNumber;
            $item->warehouse_id = $data->warehouse_id;
            $item->warehouse_type = $data->warehouse_type;
            $item->disposal_date = $data->disposal_date;
            $item->notes = $data->notes;
            $item->created_by = auth()->user()->id;
            $item->save();

            foreach ($data->details as $_s) {
                $stock = (object)$_s;
                $mdDetail = new MaterialDisposalDetail();
                $mdDetail->material_disposal_id = $item->id;
                $mdDetail->material_stock_detail_id = $stock->material_stock_detail_id;
                $mdDetail->system_bad_qty = $stock->system_bad_qty;
                $mdDetail->disposed_bad_qty = $stock->disposed_bad_qty;
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
     * download
     *
     * @param  mixed $id
     * @return void
     */
    public function download($id)
    {
        $item = MaterialDisposal::findOrFail($id);
        return Excel::download(new MaterialDisposalItemExport($id), 'Material-Disposal-Item-' . $item->number . '.xlsx');
    }

    /**
     * download_attachment
     *
     * @param  mixed $id
     * @return void
     */
    public function download_attachment($id)
    {
        $item = MaterialDisposal::findOrFail($id);
        if (!File::exists(public_path('uploads/' . $item->attachment))) {
            abort(404);
        }
        return response()->download(public_path('uploads/' . $item->attachment));
    }

    /**
     * submit
     *
     * @param  Request $request
     * @return void
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'string', Rule::exists(MaterialDisposal::class, 'id')->where('status', 0)],
            'ref_number' => 'required|string|max:64',
            'attachment' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120|mimetypes:image/png,image/jpg,image/jpeg,application/pdf'
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
            $attachment = (new UploadFileHelper())->save($request->file('attachment'));
            $item = MaterialDisposal::findOrFail($data->id);
            $item->ref_number = $data->ref_number;
            $item->attachment = $attachment;
            $item->status = 1;
            $item->closed_date = Carbon::now();
            $item->save();

            $this->updateStock($item);

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
        MaterialDisposal::whereIn('id', $ids)->delete();
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
            $stockDetail->decrement('bad_stock', $item->disposed_bad_qty);
            $stockDetail->save();

            $materialHistory = new MaterialStockHistory();
            $materialHistory->material_id = $stockDetail->material_stock->material_id;
            $materialHistory->warehouse_id = $data->warehouse_id;
            $materialHistory->project_id = $stockDetail->material_stock->project_id;
            $materialHistory->source_type = 'material-disposal';
            $materialHistory->source_id = $data->id;
            $materialHistory->bad_qty = (int)('-' . $item->disposed_bad_qty);
            $materialHistory->source_number = $data->number;
            $materialHistory->transaction_date = $data->disposal_date;
            $materialHistory->save();
        }
    }
}
