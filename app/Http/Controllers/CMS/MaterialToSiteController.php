<?php

namespace App\Http\Controllers\CMS;

use App\Helpers\UploadFileHelper;
use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialDiscrepancy;
use App\Models\MaterialDiscrepancyDetail;
use App\Models\MaterialStockDetail;
use App\Models\MaterialStockHistory;
use App\Models\MaterialToSite;
use App\Models\MaterialToSiteDetail;
use App\Models\MaterialToSiteDetailPhoto;
use App\Models\MaterialToSiteDetailStock;
use App\Models\Project;
use App\Models\Warehouse;
use App\Services\ApprovalService;
use App\Services\NotificationService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MaterialToSiteController extends Controller
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
        $items = MaterialToSite::query();

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

        if (isset($request->used_material_return) && $request->used_material_return) {
            $items->whereDoesntHave('used_material_return', function ($q) {
                $q->where('status', 1);
            });
        }

        $items->orderBy('number', 'desc');
        $items->with([
            'from_warehouse:id,code,name',
            'project:id,code,name',
            'creator:id,job_position_id,name',
            'creator.job_position:id,name',
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

            if (!isset($request->view_partial)) {
                $items->whereNull('material_to_site_id');
            }

            if (isset($request->q) && $request->q) {
                $q = $request->q;
                $items->where(function ($query) use ($q) {
                    $query->orWhere('number', 'like', '%' . $q . '%')
                        ->orWhere('ticket_number', 'like', '%' . $q . '%')
                        ->orWhere('section_name', 'like', '%' . $q . '%')
                        ->orWhere('segment_name', 'like', '%' . $q . '%')
                        ->orWhereHas('from_warehouse', function ($query) use ($q) {
                            $query->where('code', 'like', '%' . $q . '%')
                                ->orWhere('name', 'like', '%' . $q . '%');
                        })
                        ->orWhereHas('project', function ($query) use ($q) {
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
                'details.stocks:id,material_to_site_detail_id,material_stock_detail_id,qty,good_qty,bad_qty,lost_qty,notes',
                'details.stocks.material_stock_detail:id,material_stock_id,purchase_order_id,good_stock,bad_stock,lost_stock,booked_good_stock',
                'details.stocks.material_stock_detail.purchase_order:id,number,delivery_date',
                'details.stocks.material_stock_detail.material_stock:id,project_id',
                'details.stocks.material_stock_detail.material_stock.project:id,code',
                'details.photos:id,material_to_site_detail_id,photo,latitude,longitude',
                'creator:id,name,job_position_id',
                'creator.job_position:id,role_id,name',
                'creator.job_position.role:id,name',
                'approvals' => function ($q) {
                    $q->select(['job_position_id', 'type', 'type_id', 'status', 'status_name', 'status_order', 'remarks', 'show_notification', 'another_job_positions', 'updated_by', 'updated_at'])
                        ->with(['job_position:id,role_id,name', 'job_position.role:id,name', 'updater:id,name']);
                },
                'childs' => function ($q) {
                    $q->select(['id', 'material_to_site_id', 'request_date', 'received_date'])
                        ->where('status', 1)
                        ->with([
                            'details',
                            'details.material:id,number,name,uom',
                            'details.stocks:id,material_to_site_detail_id,material_stock_detail_id,qty,good_qty,bad_qty,lost_qty,notes',
                            'details.stocks.material_stock_detail:id,material_stock_id,purchase_order_id,good_stock,bad_stock,lost_stock,booked_good_stock',
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
            'from_warehouse' => ['required', 'string', Rule::exists(Warehouse::class, 'id')->where('type', $data['from_type'])],
            'project_id' => ['required', 'string', Rule::exists(Project::class, 'id')],
            'request_date' => 'required|date_format:Y-m-d',
            'jointer' => 'required|string|max:64',
            'ticket_number' => 'required|string|max:64',
            'customer_name' => 'nullable|string|max:255',
            'segment_name' => 'required|string|max:255',
            'section_name' => 'required|string|max:255',
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

        DB::beginTransaction();
        try {
            $getLast = MaterialToSite::whereDate('created_at', Carbon::now()->format('Y-m-d'))
                ->orderBy('created_at', 'DESC')
                ->orderBy('number', 'DESC')
                ->sharedLock()
                ->first();
            $lastNumber = (!$getLast) ? 0 : abs(substr($getLast->number, -3));
            $makeNumber = Carbon::now()->format('ymd') . 'OTTS' . sprintf('%03s', $lastNumber + 1);
            $cekNumber = MaterialToSite::where('number', $makeNumber)->count();
            if ($cekNumber > 0) {
                DB::rollBack();
                return response()->json([
                    'status' => Response::HTTP_CONFLICT,
                    'message' => 'Try again'
                ], Response::HTTP_CONFLICT);
            }

            $item = new MaterialToSite();
            $item->number = $makeNumber;
            $item->from_warehouse = $data->from_warehouse;
            $item->from_type = $data->from_type;
            $item->project_id = $data->project_id;
            $item->request_date = $data->request_date;
            $item->jointer = $data->jointer;
            $item->ticket_number = $data->ticket_number;
            $item->customer_name = $data->customer_name;
            $item->segment_name = $data->segment_name;
            $item->section_name = $data->section_name;
            $item->notes = $data->notes;
            $item->created_by = auth()->user()->id;
            $item->save();

            foreach ($data->details as $_d) {
                $detail = (object)$_d;
                $mtsDetail = new MaterialToSiteDetail();
                $mtsDetail->material_to_site_id = $item->id;
                $mtsDetail->material_id = $detail->material_id;
                $mtsDetail->qty = $detail->qty;
                $mtsDetail->save();

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

                    $mtsStock = new MaterialToSiteDetailStock();
                    $mtsStock->material_to_site_detail_id = $mtsDetail->id;
                    $mtsStock->material_stock_detail_id = $stock->material_stock_detail_id;
                    $mtsStock->qty = $stock->qty;
                    $mtsStock->save();
                    $checkStock->increment('booked_good_stock', $mtsStock->qty);
                    $checkStock->save();
                }
            }

            (new ApprovalService($item, 'material-to-site', $item->from_type, null, true))->createApproval();

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
            'id' => ['required', 'string', Rule::exists(MaterialToSite::class, 'id')],
            'from_type' => 'required|string|in:main,transit,lastmile',
            'from_warehouse' => ['required', 'string', Rule::exists(Warehouse::class, 'id')->where('type', $data['from_type'])],
            'project_id' => ['required', 'string', Rule::exists(Project::class, 'id')],
            'request_date' => 'required|date_format:Y-m-d',
            'jointer' => 'required|string|max:64',
            'ticket_number' => 'required|string|max:64',
            'customer_name' => 'nullable|string|max:255',
            'segment_name' => 'required|string|max:255',
            'section_name' => 'required|string|max:255',
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

        DB::beginTransaction();
        try {
            $item = MaterialToSite::where('id', $data->id)->firstOrFail();
            $item->from_warehouse = $data->from_warehouse;
            $item->project_id = $data->project_id;
            $item->from_type = $data->from_type;
            $item->request_date = $data->request_date;
            $item->jointer = $data->jointer;
            $item->ticket_number = $data->ticket_number;
            $item->customer_name = $data->customer_name;
            $item->segment_name = $data->segment_name;
            $item->section_name = $data->section_name;
            $item->notes = $data->notes;
            $item->updated_by = auth()->user()->id;

            if ($item->status == -1) {
                $approvals = new ApprovalService($item, 'material-to-site', $item->from_type, null, true);
                $approvals->remove();
                $item->status = 0;
                $approvals->createApproval();
            }

            $item->save();
            $item->details()->whereNotIn('material_id', collect($data->details ?? [])->pluck('material_id'))->forceDelete();

            foreach ($data->details as $_d) {
                $detail = (object)$_d;
                $mtsDetail = MaterialToSiteDetail::firstOrNew(['material_to_site_id' => $item->id, 'material_id' => $detail->material_id]);
                $mtsDetail->material_to_site_id = $item->id;
                $mtsDetail->material_id = $detail->material_id;
                $mtsDetail->qty = $detail->qty;
                $mtsDetail->save();
                $mtsDetail->stocks()->whereNotIn('material_stock_detail_id', collect($detail->stocks ?? [])->pluck('material_stock_detail_id'))->forceDelete();

                foreach ($detail->stocks as $_s) {
                    $stock = (object) $_s;
                    $mtsStock = MaterialToSiteDetailStock::firstOrNew(['material_to_site_detail_id' => $mtsDetail->id, 'material_stock_detail_id' => $stock->material_stock_detail_id]);

                    $checkStock = MaterialStockDetail::findOrFail($stock->material_stock_detail_id);
                    if (($checkStock->good_stock - ($checkStock->booked_good_stock - $mtsStock->qty)) < $stock->qty) {
                        DB::rollBack();
                        return response()->json([
                            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                            'message' => 'Stock of Material : ' . $checkStock->material_stock->material->name . ', Project Code : ' . $checkStock->material_stock->project->code . ', PO Number : ' . $checkStock->purchase_order->number . '  is being used in another process as much as ' . ($checkStock->booked_good_stock),
                            'material_id' => $checkStock->material_id
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }

                    $mtsStock->material_to_site_detail_id = $mtsDetail->id;
                    $mtsStock->material_stock_detail_id = $stock->material_stock_detail_id;
                    $mtsStock->qty = $stock->qty;
                    $mtsStock->save();
                    $checkStock->increment('booked_good_stock', ($mtsStock->qty - $checkStock->booked_good_stock));
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
            'id' => ['required', 'string', Rule::exists(MaterialToSite::class, 'id')]
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
            $item = MaterialToSite::where('id', $data->id)->first();
            if (!$item->need_approval) {
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'This data doesn\'t require approval/rejection at this time'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            (new ApprovalService($item, 'material-to-site', $item->from_type))->approve();

            if ($item->status == 1) {
                foreach ($item->details()->get() as $detail) {
                    $this->updateStock($detail);
                }
                $this->materialDiscrepancy($item);
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
            'id' => ['required', 'string', Rule::exists(MaterialToSite::class, 'id')],
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
            $item = MaterialToSite::where('id', $data->id)->with(['details', 'details.stocks'])->first();
            if (!$item->need_approval) {
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'This data doesn\'t require approval/rejection at this time'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($item->is_processed) {
                $item->is_onsite = 1;
                $item->is_processed = 0;
                $item->io_status = 'O';
                $item->process_remarks = $data->remarks;
                $item->save();
            } else {
                (new ApprovalService($item, 'material-to-site', $item->from_type))->reject($data->remarks);
                foreach ($item->details as $detail) {
                    foreach ($detail->stocks as $s) {
                        $stock = MaterialStockDetail::findOrFail($s->material_stock_detail_id);
                        $stock->decrement('booked_good_stock', $s->qty);
                        $stock->save();
                    }
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
     * confirm
     *
     * @param  mixed $request
     * @return void
     */
    public function confirm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'string', Rule::exists(MaterialToSite::class, 'id')]
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
            $item = MaterialToSite::where('id', $data->id)->first();
            if (!$item->need_approval) {
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'This data doesn\'t require approval/rejection at this time'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            (new ApprovalService($item, 'material-to-site', $item->from_type))->approve(true);

            $item->is_onsite = 1;
            $item->is_confirmed = 1;
            $item->save();
            $this->reduceStock($item);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $r = ['status' => Response::HTTP_OK, 'result' => 'ok'];
        return response()->json($r, Response::HTTP_OK);
    }

    /**
     * close
     *
     * @param  mixed $request
     * @return void
     */
    public function close(Request $request)
    {
        $reqs = $request->all();
        $reqs['items'] = json_decode($request->items, true);
        $validator = Validator::make($reqs, [
            'id' => ['required', 'string', Rule::exists(MaterialToSite::class, 'id')],
            'items' => 'required|array',
            'items.*.id' => ['required', 'string', Rule::exists(MaterialToSiteDetail::class, 'id')->where('material_to_site_id', $reqs['id'])],
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
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();
        DB::beginTransaction();
        try {
            $item = MaterialToSite::where('id', $data->id)->first();
            if (!$item->created_by_me) {
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'This data doesn\'t require approval/rejection at this time'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $item->is_onsite = 0;
            $item->is_processed = 1;
            $item->io_status = 'I';
            $item->received_date = Carbon::now();
            $item->save();

            $appr = $item->approvals()->with(['job_position', 'job_position.users'])
                ->where(['status' => 0, 'show_notification' => 1])
                ->first();
            foreach ($appr->job_position->users as $u) {
                $url = '/material-to-site/detail/' . $item->id;
                $type = ucwords($appr->status_name);
                $title = '1 Material To Site is waiting your ' . $type;
                $desc = 'Hi ' . $u->name . ' you have 1 Material To Site transaction awaiting your ' . $type . ' with ID ' . $item->number;
                (new NotificationService($u))->create($title, $desc, $url);
                if (!$u->wa_number) {
                    continue;
                }
                (new WhatsAppService())->sendNotification($u->wa_number, $u->name, 'Material To Site', $item->number, $type);
            }

            foreach ($data->items as $d) {
                $detail = (object)$d;
                $mtsDetail = MaterialToSiteDetail::where('id', $detail->id)->firstOrFail();
                foreach ($detail->stocks as $s) {
                    $stock = (object) $s;
                    $dStock = MaterialToSiteDetailStock::where('id', $stock->id)->firstOrFail();
                    $dStock->qty = (float)$stock->qty;
                    $dStock->good_qty = (float)$stock->good_qty;
                    $dStock->bad_qty = (float)$stock->bad_qty;
                    $dStock->lost_qty = (float)$stock->lost_qty;
                    $dStock->notes = (float)$stock->notes;
                    $dStock->save();
                }
                $mtsDetail->save();
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
            'id' => ['required', 'string', Rule::exists(MaterialToSite::class, 'id')],
            'items' => 'required|array',
            'items.*.id' => ['required', 'string', Rule::exists(MaterialToSiteDetail::class, 'id')->where('material_to_site_id', $reqs['id'])],
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

        DB::beginTransaction();
        try {
            $item = MaterialToSite::where('id', $data->id)
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

            (new ApprovalService($item, 'material-to-site', $item->from_type))->receive();

            $item->received_date = Carbon::now();
            $item->status = 1;
            $item->save();
            foreach ($data->items as $d) {
                $detail = (object)$d;
                $mtsDetail = MaterialToSiteDetail::where('id', $detail->id)->firstOrFail();
                foreach ($detail->stocks as $s) {
                    $stock = (object) $s;
                    $dStock = MaterialToSiteDetailStock::where('id', $stock->id)->firstOrFail();
                    $dStock->good_qty = $stock->good_qty;
                    $dStock->bad_qty = $stock->bad_qty;
                    $dStock->lost_qty = $stock->lost_qty;
                    $dStock->notes = $stock->notes;
                    $dStock->save();
                }
                $mtsDetail->save();
                $this->updateStock($mtsDetail);
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
        foreach (MaterialToSite::whereIn('id', $ids)->with(['details', 'details.stocks'])->get() as $d) {
            (new ApprovalService($d, 'material-to-site', $d->from_type))->remove([$d->id], false);
            foreach ($d->details as $detail) {
                foreach ($detail->stocks as $s) {
                    $stock = MaterialStockDetail::findOrFail($s->material_stock_detail_id);
                    $stock->decrement('booked_good_stock', $s->qty);
                    $stock->save();
                }
                $detail->photos()->delete();
            }
            $d->delete();
        }
        return $this->index($request);
    }

    /**
     * reduceStock
     *
     * @param  mixed $details
     * @return void
     */
    private function reduceStock($data)
    {
        foreach ($data->details()->get() as $detail) {
            foreach ($detail->stocks()->get() as $item) {
                $reduceStock = MaterialStockDetail::where('purchase_order_id', $item->material_stock_detail->purchase_order_id)
                    ->whereHas('material_stock', function ($q) use ($item, $detail) {
                        $q->where('material_id', $item->material_to_site_detail->material_id)
                            ->where('warehouse_id', $detail->material_to_site->from_warehouse)
                            ->where('project_id', $detail->material_to_site->project_id);
                    })->firstOrFail();
                $reduceStock->decrement('good_stock', $item->qty);
                $reduceStock->decrement('booked_good_stock', $item->qty);
                $reduceStock->save();

                $materialHistory = new MaterialStockHistory();
                $materialHistory->material_id = $item->material_to_site_detail->material_id;
                $materialHistory->warehouse_id = $detail->material_to_site->from_warehouse;
                $materialHistory->project_id = $detail->material_to_site->project_id;
                $materialHistory->source_type = 'material-to-site';
                $materialHistory->source_id = $detail->material_to_site_id;
                $materialHistory->good_qty = (float) ('-' . $item->qty);
                $materialHistory->source_number = $data->number;
                $materialHistory->transaction_date = $data->request_date;
                $materialHistory->save();
            }
        }
    }

    /**
     * updateStock
     *
     * @param  mixed $data
     * @return void
     */
    private function updateStock($data)
    {
        foreach ($data->stocks()->get() as $item) {
            if (!$item->good_qty) {
                continue;
            }

            $addStock = MaterialStockDetail::where('purchase_order_id', $item->material_stock_detail->purchase_order_id)
                ->whereHas('material_stock', function ($q) use ($item, $data) {
                    $q->where('material_id', $item->material_to_site_detail->material_id)
                        ->where('warehouse_id', $data->material_to_site->from_warehouse)
                        ->where('project_id', $data->material_to_site->project_id);
                })->firstOrFail();
            $addStock->increment('good_stock', $item->good_qty);
            $addStock->save();

            $materialHistory = new MaterialStockHistory();
            $materialHistory->material_id = $item->material_to_site_detail->material_id;
            $materialHistory->warehouse_id = $data->material_to_site->from_warehouse;
            $materialHistory->project_id = $data->material_to_site->project_id;
            $materialHistory->source_type = 'material-to-site';
            $materialHistory->source_id = $data->material_to_site_id;
            $materialHistory->good_qty = $item->good_qty;
            $materialHistory->source_number = $data->material_to_site->number;
            $materialHistory->transaction_date = $data->material_to_site->received_date;
            $materialHistory->save();
        }
    }

    /**
     * upload_photo
     *
     * @param  Request $request
     * @return void
     */
    public function upload_photo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detail_id' => ['required', 'string', Rule::exists(MaterialToSiteDetail::class, 'id')],
            'photo' => 'required|file|max:5120|mimes:jpg,jpeg,png|mimetypes:image/*',
            'longitude' => ['required', 'numeric', 'between:-180,180', 'regex:/^-?([0-9]{1,2}|1[0-7][0-9]|180)(\.[0-9]{1,10})?$/'],
            'latitude' => ['required', 'numeric', 'between:-90,90', 'regex:/^-?([0-9]{1,2}|1[0-7][0-9]|180)(\.[0-9]{1,10})?$/'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();
        $photo = (new UploadFileHelper())->save($request->file('photo'));
        $detailPhoto = new MaterialToSiteDetailPhoto();
        $detailPhoto->material_to_site_detail_id = $data->detail_id;
        $detailPhoto->photo = $photo;
        $detailPhoto->longitude = $data->longitude;
        $detailPhoto->latitude = $data->latitude;
        $detailPhoto->save();
        $r = ['status' => Response::HTTP_OK, 'result' => ['id' => $detailPhoto->id]];
        return response()->json($r, Response::HTTP_OK);
    }

    /**
     * remove_photo
     *
     * @param  Request $request
     * @return void
     */
    public function remove_photo(Request $request)
    {
        $ids = json_decode($request->getContent());
        foreach (MaterialToSiteDetailPhoto::whereIn('id', $ids)->get() as $item) {
            if (File::exists(public_path('uploads/' . $item->photo))) {
                File::delete(public_path('uploads/' . $item->photo));
            }
            $item->forceDelete();
        }
        $r = ['status' => Response::HTTP_OK, 'result' => 'ok'];
        return response()->json($r, Response::HTTP_OK);
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
        $matDiscr->source_type = 'material-to-site';
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
