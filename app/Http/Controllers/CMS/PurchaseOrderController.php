<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Material;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\Supplier;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
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
        $items = PurchaseOrder::query();
        $items->where('id', '!=', 'dummy-001');
        $items->orderBy('number', 'desc');
        $items->with([
            'company:id,code,name',
            'project:id,code,name',
            'supplier:id,code,name',
        ]);

        if (isset($request->to_inbound) && $request->to_inbound) {
            $items->whereDoesntHave('supplier_inbound');
        }

        if (isset($request->filter) && $request->filter) {
            $filter = json_decode($request->filter, true);
            $items->where($filter);
        }

        if ($id == null) {
            if (isset($request->q) && $request->q) {
                $q = $request->q;
                $items->where(function ($query) use ($q) {
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
     * @return mixed
     */
    public function create(Request $request)
    {
        $data = json_decode($request->data, true);
        $validator = Validator::make($data, [
            'supplier_id' => ['required', 'string', Rule::exists(Supplier::class, 'id')],
            'company_id' => ['required', 'string', Rule::exists(Company::class, 'id')],
            'project_id' => ['required', 'string', Rule::exists(Project::class, 'id')],
            'number' => ['required', 'string', Rule::unique(PurchaseOrder::class, 'number')],
            'po_date' => 'required|date_format:Y-m-d',
            'incoterms' => 'nullable|string|max:128',
            'term_of_payment' => 'nullable|string|max:128',
            'delivery_date' => 'nullable|date_format:Y-m-d',
            'notes' => 'nullable|string|max:255',
            'details' => 'required|array',
            'details.*.material_id' => ['required', 'string', Rule::exists(Material::class, 'id')],
            'details.*.qty' => 'required|numeric|min:1',
            'details.*.currency' => 'required|string|max:32',
            'details.*.price' => 'required|numeric|min:0',
            'details.*.rate' => 'required|numeric|min:1'
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
            $item = new PurchaseOrder();
            $item->supplier_id = $data->supplier_id;
            $item->company_id = $data->company_id;
            $item->project_id = $data->project_id;
            $item->number = $data->number;
            $item->po_date = $data->po_date;
            $item->incoterms = $data->incoterms;
            $item->term_of_payment = $data->term_of_payment;
            $item->delivery_date = $data->delivery_date;
            $item->notes = $data->notes;
            $item->total = 0;
            $item->created_by = auth()->user()->id;
            $item->save();

            $_total = 0;
            foreach ($data->details as $_d) {
                $detail = (object)$_d;
                $poDetail = new PurchaseOrderDetail();
                $poDetail->purchase_order_id = $item->id;
                $poDetail->material_id = $detail->material_id;
                $poDetail->qty = $detail->qty;
                $poDetail->currency = strtoupper($detail->currency);
                $poDetail->price = $detail->price;
                $poDetail->rate = $detail->rate;
                $poDetail->idr_price = $poDetail->price * $poDetail->rate;
                $poDetail->total = $poDetail->idr_price * $poDetail->qty;
                $poDetail->save();
                $_total += $poDetail->total;
            }
            $item->total = $_total;
            $item->save();

            (new ApprovalService($item, 'purchase-orders'))->createApproval();

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
            'id' => ['required', 'string', Rule::exists(PurchaseOrder::class, 'id')],
            'supplier_id' => ['required', 'string', Rule::exists(Supplier::class, 'id')],
            'company_id' => ['required', 'string', Rule::exists(Company::class, 'id')],
            'project_id' => ['required', 'string', Rule::exists(Project::class, 'id')],
            'number' => ['required', 'string', Rule::unique(PurchaseOrder::class, 'number')->ignore($data['id'])],
            'po_date' => 'required|date_format:Y-m-d',
            'incoterms' => 'nullable|string|max:128',
            'term_of_payment' => 'nullable|string|max:128',
            'delivery_date' => 'nullable|date_format:Y-m-d',
            'notes' => 'nullable|string|max:255',
            'details' => 'required|array',
            'details.*.material_id' => ['required', 'string', Rule::exists(Material::class, 'id')],
            'details.*.qty' => 'required|numeric|min:1',
            'details.*.currency' => 'required|string|max:32',
            'details.*.price' => 'required|numeric|min:0',
            'details.*.rate' => 'required|numeric|min:1'
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
            $item = PurchaseOrder::where('id', $data->id)->firstOrFail();
            if ($item->status == -1) {
                $approvalSvc = new ApprovalService($item, 'purchase-orders');
                $approvalSvc->remove();
                $item->status = 0;
                $approvalSvc->createApproval();
            }
            $item->supplier_id = $data->supplier_id;
            $item->company_id = $data->company_id;
            $item->project_id = $data->project_id;
            $item->number = $data->number;
            $item->po_date = $data->po_date;
            $item->incoterms = $data->incoterms;
            $item->term_of_payment = $data->term_of_payment;
            $item->delivery_date = $data->delivery_date;
            $item->notes = $data->notes;
            $item->updated_by = auth()->user()->id;
            $item->save();
            $item->details()->whereNotIn('material_id', collect($data->details ?? [])->pluck('material_id'))->forceDelete();

            $_total = 0;
            foreach ($data->details as $_d) {
                $detail = (object)$_d;
                $poDetail = PurchaseOrderDetail::firstOrNew(['purchase_order_id' => $item->id, 'material_id' => $detail->material_id]);
                $poDetail->purchase_order_id = $item->id;
                $poDetail->material_id = $detail->material_id;
                $poDetail->qty = $detail->qty;
                $poDetail->currency = strtoupper($detail->currency);
                $poDetail->price = $detail->price;
                $poDetail->rate = $detail->rate;
                $poDetail->idr_price = $poDetail->price * $poDetail->rate;
                $poDetail->total = $poDetail->idr_price * $poDetail->qty;
                $poDetail->save();
                $_total += $poDetail->total;
            }
            $item->total = $_total;
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
            'id' => ['required', 'string', Rule::exists(PurchaseOrder::class, 'id')]
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
            $item = PurchaseOrder::where('id', $data->id)->first();
            if (!$item->need_approval) {
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'This data doesn\'t require approval at this time'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            (new ApprovalService($item, 'purchase-orders'))->approve();

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
            'id' => ['required', 'string', Rule::exists(PurchaseOrder::class, 'id')],
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
            $item = PurchaseOrder::where('id', $data->id)->first();
            if (!$item->need_approval) {
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'This data doesn\'t require approval/rejection at this time'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            (new ApprovalService($item, 'purchase-orders'))->reject($data->remarks);

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
        $item = PurchaseOrder::whereIn('id', $ids);
        $item->delete();
        (new ApprovalService($item, 'purchase-orders'))->remove($ids, false);
        return $this->index($request);
    }
}
