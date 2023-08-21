<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\PoSap;
use App\Models\Project;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class POSapController extends Controller
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
        $items = PoSap::query();
        $items->orderBy('status', 'asc');
        $items->orderBy('created_at', 'desc');

        if (isset($request->filter) && $request->filter) {
            $filter = json_decode($request->filter, true);
            $items->where($filter);
        }

        if ($id == null) {
            if (isset($request->q) && $request->q) {
                $q = $request->q;
                $items->where(function ($query) use ($q) {
                    $query->orWhere('number', 'like', '%' . $q . '%')
                        ->orWhere('incoterms', 'like', '%' . $q . '%')
                        ->orWhere('supplier', 'like', '%' . $q . '%')
                        ->orWhereHas('details', function ($query) use ($q) {
                            $query->where('number', 'like', '%' . $q . '%')
                                ->orWhere('name', 'like', '%' . $q . '%');
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
                'project:id,company_id,code,name',
                'project.company:id,code,name',
                'activator:id,name'
            ]);
            $data['data'] = $items->where('id', $id)->first();
            $data['total'] = 1;
        }
        $r = ['status' => Response::HTTP_OK, 'result' => $data];
        return response()->json($r, Response::HTTP_OK);
    }

    /**
     * update
     *
     * @param  mixed $request
     * @return void
     */
    public function activate(Request $request)
    {
        $data = json_decode($request->data, true);
        $validator = Validator::make($data, [
            'id' => ['required', 'string', Rule::exists(PoSap::class, 'id')->where('status', 0)],
            'project_id' => ['required', 'string', Rule::exists(Project::class, 'id')],
            'delivery_date' => 'required|date_format:Y-m-d',
            'idr_rate' => 'required|numeric|min:1'
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
            $item = PoSap::find($data->id);
            $act_po_detail = [];
            foreach ($item->details()->get() as $detail) {
                $material =  Material::where('number', $detail->number)->select('id')->first();
                if (!$material) {
                    continue;
                }
                $detail->idr_price = $detail->price * $data->idr_rate;
                $detail->save();
                $act_po_detail[] = [
                    'material_id' => $material->id,
                    'qty' => $detail->qty,
                    'price' => $detail->price,
                    'rate' => $data->idr_rate,
                    'currency' => $detail->currency
                ];
            }
            if (!count($act_po_detail)) {
                DB::rollBack();
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'Materials in this PO is not defined yet, Please Add Material first to activate this PO'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $item->project_id = $data->project_id;
            $item->delivery_date = $data->delivery_date;
            $item->idr_rate = $data->idr_rate;
            $item->activated_by = auth()->user()->id;
            $item->activated_at = Carbon::now();
            $item->status = 1;
            $item->save();

            $supplierName = strlen(trim($item->supplier)) > 0 ? trim($item->supplier) : 'N/A';
            $supplier = Supplier::where('name', $supplierName)->first();
            if (!$supplier) {
                $supplier = new Supplier();
                $supplier->name = $supplierName;
                $supplier->code = substr(preg_replace('/[^a-zA-Z0-9]+/', '', $supplierName), 0, 4) . date('ymHi');
                $supplier->save();
            }
            $poData = [
                'supplier_id' => $supplier->id,
                'company_id' => $item->project->company_id,
                'project_id' => $item->project_id,
                'number' => $item->number,
                'po_date' => $item->po_date,
                'notes' => 'From SAP',
                'incoterms' => $item->incoterms,
                'term_of_payment' => $item->term_of_payment,
                'delivery_date' => $item->delivery_date,
                'details' => $act_po_detail
            ];
            $req = new Request();
            $req->merge(['data' => json_encode($poData)]);
            $res = (new PurchaseOrderController())->create($req);
            if ($res->original['status'] != 200) {
                DB::rollBack();
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'wrong' => $res->original['wrong']
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
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
        PoSap::whereIn('id', $ids)->delete();
        return $this->index($request);
    }
}
