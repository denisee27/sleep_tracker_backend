<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\Navigation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NavigationController extends Controller
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
        $item = Navigation::query();
        if (isset($request->filter) && $request->filter) {
            $filter = json_decode($request->filter, true);
            $item->where($filter);
        }
        if (isset($request->parent) && $request->parent) {
            $item->where('parent_id', $request->parent);
        }
        $item->with([
            'childs' => function ($query) use ($request, $id) {
                $query->when((isset($request->filter) && $request->filter), function ($q) use ($request, $id) {
                    $filter = json_decode($request->filter, true);
                    $q->where($filter)->when((isset($id) && is_array($id)  && count($id)), function ($q) use ($id) {
                        $q->whereIn('id', $id);
                    });
                })->with(['childs' => function ($query) use ($request, $id) {
                    $query->when((isset($request->filter) && $request->filter), function ($q) use ($request, $id) {
                        $filter = json_decode($request->filter, true);
                        $q->where($filter)->when((isset($id) && is_array($id) && count($id)), function ($q) use ($id) {
                            $q->whereIn('id', $id);
                        });
                    })
                        ->orderBy('parent_id', 'asc')
                        ->orderBy('position', 'asc')
                        ->orderBy('status', 'desc');
                }])
                    ->orderBy('parent_id', 'asc')
                    ->orderBy('position', 'asc')
                    ->orderBy('status', 'desc');
            },
            'parent'
        ]);
        if ($id == null) {
            $item->orderBy('parent_id', 'asc')
                ->orderBy('position', 'asc')
                ->orderBy('status', 'desc');
            if (isset($request->q) && $request->q) {
                $q = $request->q;
                $item->orWhere(function ($query) use ($q) {
                    $query->orWhere('name', 'like', '%' . $q . '%')
                        ->orWhere('link', 'like', '%' . $q . '%')
                        ->orWhereHas('parent', function ($query) use ($q) {
                            $query->where('name', 'like', '%' . $q . '%')
                                ->orWhere('link', 'like', '%' . $q . '%');
                        })
                        ->orWhereHas('childs', function ($query) use ($q) {
                            $query->where('name', 'like', '%' . $q . '%')
                                ->orWhere('link', 'like', '%' . $q . '%');
                        });
                });
            }
            if ((int) $request->limit > 0) {
                $data = $item->paginate(((int) $request->limit))->toArray();
            } else {
                $data['data'] = $item->get();
                $data['total'] = count($data['data']);
            }
        } else {
            if (is_array($id)) {
                $data['data'] = $item->whereIn('id', $id)->get();
            } else {
                $data['data'] = $item->where(['id' => $id])->first();
            }
        }
        $r = ['status' => Response::HTTP_OK, 'result' => $data];
        return response()->json($r, Response::HTTP_OK);
    }
    /**
     * get
     *
     * @param  mixed $request
     * @return void
     */
    public function get(Request $request)
    {
        $user = auth()->user();
        if ($user->role->access[0] == '*') {
            $request->merge(['filter' => json_encode(['status' => 1])]);
            return $this->index($request);
        }
        $user_menu = array();
        foreach ($user->role->access as $l) {
            $user_menu[] = $l['link'];
        }
        $items_c = Navigation::query();
        $items_c->where('status', 1)->where('parent_id', '!=', null)->where('type', $user->type);
        $items_c->whereIn('link', $user_menu);
        $childs = $items_c->pluck('parent_id');
        $items_s = Navigation::query();
        if (isset($request->parent) && $request->parent) {
            $items_s->where('parent_id', $request->parent);
        }
        $items_s->where('status', 1);
        $items_s->where(function ($q) use ($childs, $user_menu) {
            $q->where(function ($qw) use ($user_menu) {
                $qw->where('parent_id', null)
                    ->whereIn('link', $user_menu);
            })->orWhereIn('id', $childs);
        });
        $items_s->select(['id', 'parent_id', 'nama', 'icon', 'link']);
        $items_s->with(['childs' => function ($q) use ($user_menu) {
            $q->where('status', 1)->where('parent_id', '!=', null)->whereIn('link', $user_menu)->select(['parent_id', 'nama', 'icon', 'link'])->orderBy('position', 'asc');
        }]);
        $items_s->orderBy('position', 'asc');
        $data['data'] = $items_s->get();
        $r = ['status' => Response::HTTP_OK, 'result' => $data];
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
        $data = json_decode($request->data);
        $item = Navigation::where(['id' => $request->id])->first();
        if ($item == null) {
            return response()->json(['status' => Response::HTTP_NOT_FOUND, 'message' => 'HTTP_NOT_FOUND: Item tidak ditemukan'], Response::HTTP_NOT_FOUND);
        }
        $item->parent_id = $data->parent_id;
        $item->name = $data->name;
        $item->icon = $data->icon ?? null;
        $item->link = $data->link;
        $item->position = $data->position;
        if (isset($data->action)) {
            $action = str_replace(' ', '', $data->action);
            $action = explode(',', $action);
            $item->action = $action;
        }
        $item->status = $data->status;
        $item->save();
        $this->create_seeder();
        return $this->index($request);
    }
    /**
     * create
     *
     * @param  mixed $request
     * @return void
     */
    public function create(Request $request)
    {
        $data = json_decode($request->data);
        $item = new Navigation();
        $item->parent_id = (isset($data->parent_id) && is_int($data->parent_id) ? $data->parent_id : null);
        $item->name = $data->name;
        $item->icon = $data->icon ?? null;
        $item->link = (isset($data->link) ? $data->link : null);
        $item->position = (isset($data->position) ? $data->position : null);
        if (isset($data->action)) {
            $action = str_replace(' ', '', $data->action);
            $action = explode(',', $action);
            $item->action = $action;
        }
        $item->status = (isset($data->status) ? $data->status : 0);
        $item->save();
        $this->create_seeder();
        return $this->index($request);
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
        Navigation::whereIn('id', $ids)->delete();
        $this->create_seeder();
        return $this->index($request);
    }

    /**
     * create_seeder
     *
     * @return void
     */
    private function create_seeder()
    {
        $sql = "INSERT INTO `navigations` (`id`, `parent_id`, `name`, `icon`, `link`, `action`, `position`, `status`, `created_at`, `updated_at`) VALUES\n";
        $items = Navigation::all();
        foreach ($items as $i => $item) {
            $sql .= "\t\t\t\t\t\t\t\t\t   (" . $item->id . ", " . ($item->parent_id ?? 'NULL') . ", '" . $item->name . "', '" . $item->icon . "', '" . $item->link . "', '" . addslashes(json_encode($item->action)) . "', " . $item->position . ", " . $item->status . ", '" . $item->created_at . "', '" . $item->updated_at . "')";
            $sql .= ($i == count($items) - 1) ? ';' : ',';
            $sql .= "\n";
        }
        $fileStr = file_get_contents(database_path('seeders/NavigationSeeder.php'));
        $fileStr = preg_replace('/\$sql = "(.|\n)*"/', '$sql = "' . "\n\t\t" . $sql . "\t\t" . '"', $fileStr);
        file_put_contents(database_path('seeders/NavigationSeeder.php'), $fileStr);
    }
}
