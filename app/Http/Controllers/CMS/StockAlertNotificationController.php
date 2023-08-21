<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\StockAlertNotification;
use App\Models\StockAlertNotificationUser;
use App\Models\StockAlertNotificationWarehouse;
use App\Models\User;
use App\Models\Warehouse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StockAlertNotificationController extends Controller
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
        $items = StockAlertNotification::query();
        $items->orderBy('created_at', 'desc');
        $items->with([
            'warehouses',
            'warehouses.warehouse:id,type,name',
            'users',
            'users.user:id,job_position_id,name'
        ]);
        if (isset($request->filter) && $request->filter) {
            $filter = json_decode($request->filter, true);
            $items->where($filter);
        }

        if ($id == null) {
            if (isset($request->q) && $request->q) {
                $q = $request->q;
                $items->where(function ($query) use ($q) {
                    $query->whereHas('users', function ($query) use ($q) {
                        $query->whereHas('user', function ($query) use ($q) {
                            $query->where('name', 'like', '%' . $q . '%')
                                ->orWhere('email', 'like', '%' . $q . '%')
                                ->orWhere('nik', 'like', '%' . $q . '%')
                                ->orWhereHas('job_position', function ($query) use ($q) {
                                    $query->where('name', 'like', '%' . $q . '%');
                                });
                        });
                    })->orWhereHas('warehouses', function ($query) use ($q) {
                        $query->whereHas('warehouse', function ($query) use ($q) {
                            $query->where('name', 'like', '%' . $q . '%')
                                ->orWhere('code', 'like', '%' . $q . '%')
                                ->orWhere('type', 'like', '%' . $q . '%');
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
            $data['data'] = $items->where('id', $id)->first();
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
            'users' => 'required|array',
            'users.*' => ['required', 'string', Rule::exists(User::class, 'id')],
            'warehouses' => 'required|array',
            'warehouses.*' => ['required', 'string', Rule::exists(Warehouse::class, 'id')],
            'status' => 'required|numeric|in:0,1'
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
            $item = new StockAlertNotification();
            $item->status = $data->status;
            $item->save();

            foreach ($data->users as $user) {
                $sUser = new StockAlertNotificationUser();
                $sUser->stock_alert_notification_id = $item->id;
                $sUser->user_id = $user;
                $sUser->save();
            }

            foreach ($data->warehouses as $warehouse) {
                $sWarehouse = new StockAlertNotificationWarehouse();
                $sWarehouse->stock_alert_notification_id = $item->id;
                $sWarehouse->warehouse_id = $warehouse;
                $sWarehouse->save();
            }

            DB::commit();
        } catch (Exception $e) {
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
            'id' => ['required', 'string', Rule::exists(StockAlertNotification::class, 'id')],
            'users' => 'required|array',
            'users.*' => ['required', 'string', Rule::exists(User::class, 'id')],
            'warehouses' => 'required|array',
            'warehouses.*' => ['required', 'string', Rule::exists(Warehouse::class, 'id')],
            'status' => 'required|numeric|in:0,1'
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
            $item = StockAlertNotification::findOrFail($data->id);
            $item->save();

            $item->users()->whereNotIn('user_id', $data->users)->delete();
            $item->warehouses()->whereNotIn('warehouse_id', $data->warehouses)->delete();
            $item->refresh();

            foreach ($data->users as $user) {
                $sUser = StockAlertNotificationUser::firstOrNew(['stock_alert_notification_id' => $item->id, 'user_id' => $user]);
                $sUser->stock_alert_notification_id = $item->id;
                $sUser->user_id = $user;
                $sUser->save();
            }

            foreach ($data->warehouses as $warehouse) {
                $sWarehouses = StockAlertNotificationWarehouse::firstOrNew(['stock_alert_notification_id' => $item->id, 'warehouse_id' => $warehouse]);
                $sWarehouses->stock_alert_notification_id = $item->id;
                $sWarehouses->warehouse_id = $warehouse;
                $sWarehouses->save();
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

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
            'id' => ['required', 'string', Rule::exists(StockAlertNotification::class, 'id')],
            'status' => 'required|numeric:in:0,1'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();
        $item = StockAlertNotification::where('id', $data->id)->first();
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
        StockAlertNotification::whereIn('id', $ids)->delete();
        StockAlertNotificationUser::whereIn('stock_alert_notification_id', $ids)->delete();
        StockAlertNotificationWarehouse::whereIn('stock_alert_notification_id', $ids)->delete();
        return $this->index($request);
    }
}
