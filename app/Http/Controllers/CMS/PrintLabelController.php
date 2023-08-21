<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialStockDetail;
use App\Models\Warehouse;
use chillerlan\QRCode\QRCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PrintLabelController extends Controller
{
    /**
     * index
     *
     * @param  mixed $request
     * @return void
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'material_id' => ['required_without:warehouse_id', 'string', Rule::exists(Material::class, 'id')],
            'warehouse_id' => ['required_without:material_id', 'string', Rule::exists(Warehouse::class, 'id')],
            'ids' => 'nullable|string'
        ]);
        if ($validator->fails()) {
            return response('<h1>Oops!</h1><h2>' . $validator->errors()->first() . '</h2>');
        }
        $data = (object)$validator->validated();
        $items = MaterialStockDetail::query();
        if (isset($data->material_id) && $data->material_id) {
            $items->whereHas('material_stock', function ($q) use ($data) {
                $q->where('material_id', $data->material_id);
            });
        }
        if (isset($data->warehouse_id) && $data->warehouse_id) {
            $items->whereHas('material_stock', function ($q) use ($data) {
                $q->where('warehouse_id', $data->warehouse_id);
            });
        }
        if (isset($data->ids) && $data->ids) {
            $ids = explode(',', $data->ids);
            $items->whereIn('id', $ids);
        }
        $items->with([
            'material_stock',
            'material_stock.material:id,number,name',
            'material_stock.warehouse:id,code,name',
            'purchase_order:id,number'
        ]);
        $lists = $items->get();
        if (!count($lists)) {
            return response('<h1>Oops!</h1><h2>Data not found</h2>');
        }
        return response()->make(view('print.printLabel', ['items' => $lists]));
    }
}
