<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\ApprovalSetting;
use App\Models\ApprovalSettingException;
use App\Models\ApprovalSettingRule;
use App\Models\Category;
use App\Models\Role;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ApprovalSettingController extends Controller
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
        $items = ApprovalSetting::query();
        $items->orderBy('name', 'asc');
        $items->orderBy('origin', 'asc');
        $items->orderBy('destination', 'asc');
        $items->with(['rules', 'rules.role', 'exceptions', 'exceptions.category']);

        if (isset($request->filter) && $request->filter) {
            $filter = json_decode($request->filter, true);
            $items->where($filter);
        }

        if ($id == null) {
            if (isset($request->q) && $request->q) {
                $q = $request->q;
                $items->where(function ($query) use ($q) {
                    $query->orWhere('name', 'like', '%' . $q . '%')
                        ->orWhere('type', 'like', '%' . $q . '%')
                        ->orWhere('origin', 'like', '%' . $q . '%')
                        ->orWhere('destination', 'like', '%' . $q . '%')
                        ->orWhereHas('rules', function ($query) use ($q) {
                            $query->whereHas('role', function ($query) use ($q) {
                                $query->where('name', 'like', '%' . $q . '%');
                            });
                        })
                        ->orWhereHas('exceptions', function ($query) use ($q) {
                            $query->whereHas('category', function ($query) use ($q) {
                                $query->where('name', 'like', '%' . $q . '%');
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
            'name' => 'required|string|max:128',
            'origin' => 'required|string|max:128',
            'destination' => 'required|string|max:128',
            'conditions' => 'nullable|string',
            'rules' => 'required|array',
            'rules.*.role_id' => ['required', 'string', Rule::exists(Role::class, 'id')],
            'rules.*.level_name' => 'required|string',
            'rules.*.level_order' => 'required|numeric|min:0',
            'exceptions' => 'nullable|array',
            'exceptions.*.id' => ['nullable', 'string', Rule::exists(ApprovalSettingException::class, 'id')],
            'exceptions.*.category_id' => ['nullable', 'string', Rule::exists(Category::class, 'id')],
            'exceptions.*.type' => 'nullable|string|in:>,<,==,>=,<=',
            'exceptions.*.qty' => 'nullable|numeric|min:0',
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
            $item = new ApprovalSetting();
            $item->name = $data->name;
            $item->type = Str::slug($data->name);
            $item->origin = $data->origin;
            $item->destination = $data->destination;
            $item->conditions = $data->conditions ?? null;
            $item->status = $data->status;
            $item->save();

            foreach ($data->rules as $_rule) {
                $rule = (object)$_rule;
                $approvalRule = new ApprovalSettingRule();
                $approvalRule->approval_setting_id = $item->id;
                $approvalRule->role_id = $rule->role_id;
                $approvalRule->level_name = $rule->level_name;
                $approvalRule->level_order = $rule->level_order;
                $approvalRule->save();
            }

            foreach (($data->exceptions ?? []) as $_exception) {
                $exception = (object)$_exception;
                $approvalRule = new ApprovalSettingException();
                $approvalRule->approval_setting_id = $item->id;
                $approvalRule->category_id = $exception->category_id;
                $approvalRule->type = $exception->type;
                $approvalRule->qty = $exception->qty;
                $approvalRule->save();
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
            'id' => ['required', 'string', Rule::exists(ApprovalSetting::class, 'id')],
            'name' => 'required|string|max:128',
            'origin' => 'required|string|max:128',
            'destination' => 'required|string|max:128',
            'conditions' => 'nullable|string',
            'rules' => 'required|array',
            'rules.*.id' => ['nullable', 'string', Rule::exists(ApprovalSettingRule::class, 'id')],
            'rules.*.role_id' => ['required', 'string', Rule::exists(Role::class, 'id')],
            'rules.*.level_name' => 'required|string',
            'rules.*.level_order' => 'required|numeric|min:0',
            'exceptions' => 'nullable|array',
            'exceptions.*.id' => ['nullable', 'string', Rule::exists(ApprovalSettingException::class, 'id')],
            'exceptions.*.category_id' => ['nullable', 'string', Rule::exists(Category::class, 'id')],
            'exceptions.*.type' => 'nullable|string|in:>,<,==,>=,<=',
            'exceptions.*.qty' => 'nullable|numeric|min:0',
            'status' => 'required|numeric:in:0,1'
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
            $item = ApprovalSetting::findOrFail($data->id);
            $item->name = $data->name;
            $item->type = Str::slug($data->name);
            $item->origin = $data->origin;
            $item->destination = $data->destination;
            $item->conditions = $data->conditions;
            $item->status = $data->status;
            $item->save();

            $item->rules()->whereNotIn('id', collect($data->rules ?? [])->pluck('id'))->delete();
            $item->exceptions()->whereNotIn('id', collect($data->exceptions ?? [])->pluck('id'))->delete();
            $item->refresh();

            foreach ($data->rules as $_rule) {
                $rule = (object)$_rule;
                $approvalRule = ApprovalSettingRule::firstOrNew(['id' => $rule->id ?? null]);
                $approvalRule->approval_setting_id = $item->id;
                $approvalRule->role_id = $rule->role_id;
                $approvalRule->level_name = $rule->level_name;
                $approvalRule->level_order = $rule->level_order;
                $approvalRule->save();
            }

            foreach (($data->exceptions ?? []) as $_exception) {
                $exception = (object)$_exception;
                $approvalRule = ApprovalSettingException::firstOrNew(['id' => $exception->id ?? null]);
                $approvalRule->approval_setting_id = $item->id;
                $approvalRule->category_id = $exception->category_id;
                $approvalRule->type = $exception->type;
                $approvalRule->qty = $exception->qty;
                $approvalRule->save();
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
            'id' => ['required', 'string', Rule::exists(ApprovalSetting::class, 'id')],
            'status' => 'required|numeric:in:0,1'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();
        $item = ApprovalSetting::where('id', $data->id)->first();
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
        ApprovalSetting::whereIn('id', $ids)->delete();
        return $this->index($request);
    }
}
