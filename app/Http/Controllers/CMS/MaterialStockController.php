<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\MaterialStock;
use App\Models\MaterialStockDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MaterialStockController extends Controller
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
        DB::statement("SET SQL_MODE=''");
        $items = MaterialStock::query();
        $items->with([
            'material:id,category_id,number,name,uom,is_fifo',
            'material.category:id,name',
            'project:id,code,name',
            'details' => function ($q) {
                $q->selectRaw('material_stock_details.id,material_stock_id,purchase_order_id,good_stock,bad_stock,lost_stock,booked_good_stock,booked_bad_stock,booked_lost_stock,purchase_orders.delivery_date')
                    ->join('purchase_orders', 'purchase_orders.id', '=', 'material_stock_details.purchase_order_id')
                    ->with('purchase_order:id,number,delivery_date')
                    ->orderBy('delivery_date', 'asc');
            }
        ]);

        if (isset($request->filter) && $request->filter) {
            $filter = json_decode($request->filter, true);
            $items->where($filter);
        }

        if (isset($request->warehouse_id)) {
            $items->where('warehouse_id', $request->warehouse_id);
        }

        if (isset($request->project_id)) {
            $items->where('project_id', $request->project_id);
        }

        if (isset($request->in_project_code)) {
            $items->whereHas('project', function ($q) use ($request) {
                $q->whereIn('code', json_decode($request->in_project_code));
            });
        }

        if (isset($request->in_material_id)) {
            $items->whereIn('material_id', json_decode($request->in_material_id));
        }

        if (isset($request->bad_stock)) {
            $items->whereHas('details', function ($q) {
                $q->where('bad_stock', '>', 0);
            });
        } else {
            $items->whereHas('details', function ($q) {
                $q->where('good_stock', '>', 0);
            });
        }

        if ($id == null) {
            if (isset($request->q) && $request->q) {
                $q = $request->q;
                $items->where(function ($query) use ($q) {
                    $query->whereHas('material', function ($query) use ($q) {
                        $regex = str_replace(' ', '|', $q);
                        $query->where('name', 'rlike', $regex)
                            ->orWhere('number', 'like', '%' . $q . '%');
                    })->orWhereHas('project', function ($query) use ($q) {
                        $query->where('name', 'like', '%' . $q . '%')
                            ->orWhere('code', 'like', '%' . $q . '%');
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
            $data['data'] = $items->where('id', $id)->first();
            $data['total'] = 1;
        }
        $r = ['status' => Response::HTTP_OK, 'result' => $data];
        return response()->json($r, Response::HTTP_OK);
    }

    /**
     * reduce_fifo
     *
     * @param  mixed $request
     * @return void
     */
    public function reduce_fifo(Request $request)
    {
        $data['data'] = json_decode($request->data, true);
        $validator = Validator::make($data, [
            'data' => ['required', 'array'],
            'data.*.id' => ['required', 'string', Rule::exists(MaterialStock::class, 'id')],
            'data.*.qty' => 'required|numeric|min:0'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();
        $result = [];
        foreach ($data->data as $_i) {
            $item = (object)$_i;
            $qty = $item->qty;
            $ids = [];
            reduce:
            $cut_stock = 0;
            $mstock = MaterialStockDetail::where('material_stock_id', $item->id)
                ->when(count($ids), function ($q) use ($ids) {
                    $q->whereNotIn("material_stock_details.id", $ids);
                })
                ->selectRaw('material_stock_details.id,material_stock_id,purchase_order_id,(good_stock-booked_good_stock) as c_stock,good_stock,bad_stock,lost_stock,booked_good_stock,booked_bad_stock,booked_lost_stock,purchase_orders.delivery_date')
                ->join('purchase_orders', 'purchase_orders.id', '=', 'material_stock_details.purchase_order_id')
                ->with('purchase_order:id,number,delivery_date')
                ->orderBy('delivery_date', 'asc')
                ->first();
            if (!$mstock) {
                goto end;
            }
            $mstock->disabled = true;
            $cut_stock = ($mstock->c_stock + (isset($request->is_edit) && $request->is_edit ? $item->qty : 0)) - $qty;
            if ($cut_stock < 0) {
                $mstock->qty = $mstock->c_stock;
                $qty = abs($cut_stock);
                $ids[] = $mstock->id;
                $result[] = $mstock;
                goto reduce;
            } else {
                $mstock->qty = $qty;
                $result[] = $mstock;
            }
            end:
        }
        $r = ['status' => Response::HTTP_OK, 'result' => $result];
        return response()->json($r, Response::HTTP_OK);
    }
}
