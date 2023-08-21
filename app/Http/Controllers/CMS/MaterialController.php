<?php

namespace App\Http\Controllers\CMS;

use App\Exports\MaterialExport;
use App\Helpers\UploadFileHelper;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Material;
use App\Models\MaterialStockDetail;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class MaterialController extends Controller
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
        $items = Material::query();
        $items->orderBy('number', 'asc');
        $items->with(['category:id,name']);

        if (isset($request->filter) && $request->filter) {
            $filter = json_decode($request->filter, true);
            $items->where($filter);
        }

        if (isset($request->with_stock) && $request->with_stock) {
            $items->with([
                'stocks:material_id,purchase_order_id,good_stock',
                'stocks.purchase_order:id,number,project_id',
                'stocks.purchase_order.project:id,code'
            ]);
        }

        if (isset($request->have_stock) && isset($request->warehouse_id)) {
            $items->whereHas('stocks', function ($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        if ($id == null) {
            if (isset($request->q) && $request->q) {
                $q = $request->q;
                $items->where(function ($query) use ($q) {
                    $regex = str_replace(' ', '|', $q);
                    $query->orWhere('number', 'like', '%' . $q . '%')
                        ->orWhere('name', 'rlike', $regex)
                        ->orWhere('description', 'like', '%' . $q . '%')
                        ->orWhereHas('category', function ($query) use ($q) {
                            $query->where('name', 'like', '%' . $q . '%');
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
     * stocks
     *
     * @param  mixed $request
     * @param  mixed $id
     * @return void
     */
    public function stocks(Request $request, $id)
    {
        $data = [];
        $items = Warehouse::query();
        $items->whereHas('stocks', function ($q) use ($id) {
            $q->where('material_id', $id);
        });
        $items->where('status', 1);
        $items->select(['id', 'type', 'name']);
        $limit = $request->limit ?? 10;
        $data = $items->paginate((int)$limit)->toArray();
        $data['data'] = collect($data['data'])->map(function ($i) use ($id) {
            $i['stocks'] = MaterialStockDetail::whereHas('material_stock', function ($q) use ($i, $id) {
                $q->where('material_id', $id)
                    ->where('warehouse_id', $i['id']);
            })->with([
                'material_stock:id,project_id',
                'material_stock.project:id,code',
                'purchase_order:id,number,delivery_date'
            ])->get();
            return $i;
        })->all();
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
        $data['image'] = $request->file('image');
        $validator = Validator::make($data, [
            'category_id' => ['required', 'string', Rule::exists(Category::class, 'id')],
            'number' => ['required', 'alpha_num', 'max:128', Rule::unique(Material::class, 'number')],
            'name' => 'required|string|max:128',
            'description' => 'nullable|string|max:255',
            'uom' => 'required|string|max:128',
            'minimum_stock' => 'nullable|array',
            'is_fifo' => 'required|numeric:in:0,1',
            'status' => 'required|numeric:in:0,1',
            'image' => 'nullable|file|mimes:jpg,jpeg,png|max:1024|mimetypes:image/png,image/jpg,image/jpeg'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();

        $image = null;
        if ($request->hasFile('image')) {
            $image = (new UploadFileHelper(true))->save($request->file('image'));
        }

        $item = new Material();
        $item->category_id = $data->category_id;
        $item->number = $data->number;
        $item->name = ucwords(strtolower($data->name));
        $item->uom = strtoupper($data->uom);
        $item->description = $data->description;
        $item->minimum_stock = $data->minimum_stock;
        $item->status = $data->status;
        $item->is_fifo = $data->is_fifo;
        $item->image = $image;
        $item->save();
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
        $data['image'] = $request->file('image');
        $validator = Validator::make($data, [
            'id' => ['required', 'string', Rule::exists(Material::class, 'id')],
            'category_id' => ['required', 'string', Rule::exists(Category::class, 'id')],
            'number' => ['required', 'alpha_num', 'max:128', Rule::unique(Material::class, 'number')->ignore($data['id'])],
            'name' => 'required|string|max:128',
            'description' => 'nullable|string|max:255',
            'uom' => 'required|string|max:128',
            'minimum_stock' => 'nullable|array',
            'is_fifo' => 'required|numeric:in:0,1',
            'status' => 'required|numeric:in:0,1',
            'image' => 'nullable|file|mimes:jpg,jpeg,png|max:1024|mimetypes:image/png,image/jpg,image/jpeg'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();
        $item = Material::where('id', $data->id)->first();

        $image = $item->image;
        if ($request->hasFile('image')) {
            if (File::exists(public_path('uploads/' . $item->image))) {
                File::delete(public_path('uploads/' . $item->image));
            }
            $image = (new UploadFileHelper(true))->save($request->file('image'));
        }

        $item->category_id = $data->category_id;
        $item->number = $data->number;
        $item->name = ucwords(strtolower($data->name));
        $item->uom = strtoupper($data->uom);
        $item->description = $data->description;
        $item->minimum_stock = $data->minimum_stock;
        $item->status = $data->status;
        $item->is_fifo = $data->is_fifo;
        $item->image = $image;
        $item->save();
        $r = ['status' => Response::HTTP_OK, 'result' => 'ok'];
        return response()->json($r, Response::HTTP_OK);
    }

    /**
     * set_status
     *
     * @param  mixed $request
     * @return void
     */
    public function set_status(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'string', Rule::exists(Material::class, 'id')],
            'status' => 'required|numeric:in:0,1'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();
        $item = Material::where('id', $data->id)->first();
        $item->status = $data->status;
        $item->save();
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
        Material::whereIn('id', $ids)->delete();
        return $this->index($request);
    }

    /**
     * download
     *
     * @param  mixed $request
     * @return void
     */
    public function download(Request $request)
    {
        return Excel::download(new MaterialExport($request), 'IMS-Material-List.xlsx');
    }
}
