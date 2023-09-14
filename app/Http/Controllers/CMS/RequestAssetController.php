<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\RequestAsset;
use App\Models\RequestAssetDetail;
use App\Models\SubCategory;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RequestAssetController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param  mixed $request
     * @param  mixed $id
     */
    public function index(Request $request, $id = null)
    {
        $data = [];
        $items = RequestAsset::query();
        $items->orderBy('created_at', 'desc');
        $items->with([
            'company:id,code',
            'creator:id,role_id,name',
            'creator.role:id,name'
        ]);

        if (isset($request->filter) && $request->filter) {
            $filter = json_decode($request->filter, true);
            $items->where($filter);
        }
        if ($id == null) {
            if (isset($request->q) && $request->q) {
                $q = $request->q;
                $items->where(function ($query) use ($q) {
                    $query->where('name', 'like', '%' . $q . '%')
                        ->orWhere('code', 'like', '%' . $q . '%');
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
                'details.subcategory:id,code,name,uom',
            ]);
            $data['data'] = $items->where('id', $id)->first();
            $data['total'] = 1;
        }
        $r = ['status' => Response::HTTP_OK, 'result' => $data];
        return response()->json($r, Response::HTTP_OK);
    }

    /**
     * Show the form for creating a new resource.
     *
     *
     */
    public function create(Request $request)
    {
        $data = json_decode($request->data, true);
        $validator = Validator::make($data, [
            'company_id' => ['required', 'string', Rule::exists(Company::class, 'id')],
            'request_date' => 'required|date_format:Y-m-d',
            'notes' => 'nullable|string|max:255',
            'details' => 'required|array',
            'details.*.sub_category_id' => ['required', 'string', Rule::exists(SubCategory::class, 'id')],
            'details.*.qty' => 'required|numeric|min:1',
            'details.*.detail' => 'nullable|string|max:255',
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
            $getLast = RequestAsset::whereDate('created_at', Carbon::now()->format('Y-m-d'))
                ->orderBy('created_at', 'DESC')
                ->orderBy('number', 'DESC')
                ->sharedLock()
                ->first();
            $lastNumber = (!$getLast) ? 0 : abs(substr($getLast->number, -3));
            $makeNumber = Carbon::now()->format('ymd') . 'RQAST' . sprintf('%03s', $lastNumber + 1);
            $cekNumber = RequestAsset::where('number', $makeNumber)->count();
            if ($cekNumber > 0) {
                DB::rollBack();
                return response()->json([
                    'status' => Response::HTTP_CONFLICT,
                    'message' => 'Try again'
                ], Response::HTTP_CONFLICT);
            }
            $item = new RequestAsset();
            $item->company_id = $data->company_id;
            $item->number = $makeNumber;
            $item->request_date = $data->request_date;
            $item->notes = $data->notes;
            $item->created_by = auth()->user()->id;
            $item->save();
            foreach ($data->details as $_d) {
                $detail = (object)$_d;
                $poDetail = new RequestAssetDetail();
                $poDetail->request_asset_id = $item->id;
                $poDetail->sub_category_id = $detail->sub_category_id;
                $poDetail->qty = $detail->qty;
                $poDetail->description = $detail->detail;
                $poDetail->uom = ucwords($detail->uom);
                $poDetail->save();
            }
            $item->save();
            // (new ApprovalService($item, 'purchase-orders'))->createApproval();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
        $r = ['status' => Response::HTTP_OK, 'result' => 'ok'];
        return response()->json($r, Response::HTTP_OK);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $data = json_decode($request->data, true);
        $validator = Validator::make($data, [
            'id' => ['required', 'string', Rule::exists(RequestAsset::class, 'id')],
            'company_id' => ['required', 'string', Rule::exists(Company::class, 'id')],
            'request_date' => 'required|date_format:Y-m-d',
            'notes' => 'nullable|string|max:255',
            'details' => 'required|array',
            'details.*.sub_category_id' => ['required', 'string', Rule::exists(SubCategory::class, 'id')],
            'details.*.qty' => 'required|numeric|min:1',
            'details.*.detail' => 'nullable|string|max:255',
            'details.*.uom' => 'nullable|string',
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
            
            $item = RequestAsset::where('id', $data->id)->firstOrFail();
            if ($item->status == -1) {
                $approvalSvc = new ApprovalService($item, 'purchase-orders');
                $approvalSvc->remove();
                $item->status = 0;
                $approvalSvc->createApproval();
            }
            $item->company_id = $data->company_id;
            $item->request_date = $data->request_date;
            $item->notes = $data->notes;
            $item->updated_by = auth()->user()->id;
            $item->save();
            $item->details()->forceDelete();
            foreach ($data->details as $row) {
                $detail = (object)$row;
                $requestDetail = new RequestAssetDetail();
                $requestDetail->request_asset_id = $item->id;
                $requestDetail->sub_category_id = $detail->sub_category_id;
                $requestDetail->qty = $detail->qty;
                $requestDetail->description = $detail->detail;
                $requestDetail->uom = ucwords($detail->uom);
                $requestDetail->save();
            }
            $item->save();
            // (new ApprovalService($item, 'purchase-orders'))->createApproval();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
        $r = ['status' => Response::HTTP_OK, 'result' => 'ok'];
        return response()->json($r, Response::HTTP_OK);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
