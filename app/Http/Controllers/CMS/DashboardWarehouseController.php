<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\MaterialStockHistory;
use App\Models\MaterialToSite;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Response;

class DashboardWarehouseController extends Controller
{
    /**
     * company_id
     *
     * @var mixed
     */
    protected $company_id;

    /**
     * from
     *
     * @var mixed
     */
    protected $from;

    /**
     * to
     *
     * @var mixed
     */
    protected $to;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        $req = (object)request()->all();
        $this->from = $req->from ?? Carbon::now()->subDays(6)->format('Y-m-d');
        $this->to = $req->to ?? Carbon::now()->format('Y-m-d');
        $this->company_id = $req->company_id ?? null;
    }

    /**
     * finance
     *
     * @return void
     */
    public function index()
    {
        $data['count']['main'] = $this->getAlert('main');
        $data['count']['transit'] = $this->getAlert('transit');
        $data['count']['lastmile'] = $this->getAlert('lastmile');

        $data['transaction'] = $this->transactionToSite();
        $data['out'] = $this->materialOut();
        $data['stock'] = $this->materialStock();
        $data['stock_main'] = $this->materialStockMain();
        $data['bad_lost_stock'] = $this->materialBadLostStock();
        $data['stock_vs_out'] = $this->OutVsStock();

        $r = ['status' => Response::HTTP_OK, 'result' => $data];
        return response()->json($r, Response::HTTP_OK);
    }

    /**
     * getAlert
     *
     * @param  mixed $type
     * @return mixed
     */
    private function getAlert($type)
    {
        $items = Material::query();
        $items->where('status', 1);
        $items->select(['id', 'number', 'name', 'uom', 'minimum_stock']);
        $items->whereHas('stocks');
        $items->with([
            'stocks' => function ($q) use ($type) {
                $q->where('project_id', '!=', 'dummy-001')
                    ->whereHas('warehouse', function ($q) use ($type) {
                        $q->where('type', $type);
                    })
                    ->withSum('details as stock', 'good_stock')
                    ->with(['warehouse:id,name']);
            }
        ]);
        return $items->get()->map(function ($i) use ($type) {
            $i->total_stock = collect($i->stocks)->sum('stock');
            $i->warehouse = collect($i->stocks)->groupBy('warehouse_id')->map(function ($e) {
                $firstRow = $e->first();
                return [
                    'id' => $firstRow->warehouse_id,
                    'stock' => $e->sum('stock'),
                    'name' => $firstRow->warehouse->name
                ];
            })->values()->filter(function ($e) use ($i, $type) {
                if (!isset($i->minimum_stock[$type])) {
                    return false;
                }
                return $e['stock'] < ((float)$i->minimum_stock[$type]);
            })->all();
            return $i;
        })->filter(function ($i) use ($type) {
            return collect($i->warehouse)->filter(function ($e) use ($i, $type) {
                return $e['stock'] < ((float)$i->minimum_stock[$type]);
            })->count() > 0;
        })->count();
    }

    /**
     * getWH
     *
     * @param  mixed $type
     * @return mixed
     */
    private function  getWH($type = null)
    {
        return Warehouse::where('status', 1)
            ->when($type, function ($q) use ($type) {
                $q->where('type', $type);
            })
            ->orderBy('name', 'ASC')
            ->get();
    }

    /**
     * transactionToSite
     *
     * @return void
     */
    private function transactionToSite()
    {
        $items = [];
        foreach ($this->getWH('lastmile') as $wh) {
            $item = MaterialToSite::whereBetween('request_date', [$this->from, $this->to])
                ->selectRaw("COUNT(material_to_sites.id) as cnt, projects.name as project")
                ->join('projects', 'projects.id', '=', 'material_to_sites.project_id')
                ->where('from_warehouse', $wh->id)
                ->where('is_confirmed', 1)
                ->when($this->company_id, function ($q) {
                    $q->whereRaw("projects.company_id ='" . $this->company_id . "'");
                })
                ->groupBy('project')
                ->get();
            if (!count($item)) {
                continue;
            }
            $items[] = ['name' => $wh->name, 'data' => $item];
        }
        return $items;
    }

    /**
     * transactionToSite
     *
     * @return void
     */
    private function materialOut()
    {
        $items = [];
        foreach ($this->getWH('lastmile') as $wh) {
            $item = MaterialStockHistory::whereBetween('transaction_date', [$this->from, $this->to])
                ->where('project_id', '!=', 'dummy-001')
                ->selectRaw("ABS(SUM(good_qty)) as qty, categories.name as category")
                ->leftJoin('materials', 'materials.id', '=', 'material_stock_histories.material_id')
                ->leftJoin('categories', 'categories.id', '=', 'materials.category_id')
                ->where('warehouse_id', $wh->id)
                ->where('good_qty', '<', 0)
                ->whereIn('source_type', ['material-to-site', 'stock-opname'])
                ->when($this->company_id, function ($q) {
                    $q->join('projects', 'projects.id', '=', 'material_stock_histories.project_id')
                        ->whereRaw("projects.company_id ='" . $this->company_id . "'");
                })
                ->groupBy('category')
                ->get();
            if (!count($item)) {
                continue;
            }
            $items[] = ['name' => $wh->name, 'data' => $item];
        }
        return $items;
    }

    /**
     * materialStock
     *
     * @return void
     */
    private function materialStock()
    {
        $items = [];
        foreach ($this->getWH('lastmile') as $wh) {
            $item = MaterialStock::where('project_id', '!=', 'dummy-001')
                ->selectRaw("SUM(material_stock_details.good_stock) as qty, categories.name as category")
                ->leftJoin('material_stock_details', 'material_stock_details.material_stock_id', '=', 'material_stocks.id')
                ->leftJoin('materials', 'materials.id', '=', 'material_stocks.material_id')
                ->leftJoin('categories', 'categories.id', '=', 'materials.category_id')
                ->where('warehouse_id', $wh->id)
                ->when($this->company_id, function ($q) {
                    $q->join('projects', 'projects.id', '=', 'material_stocks.project_id')
                        ->whereRaw("projects.company_id ='" . $this->company_id . "'");
                })
                ->groupBy('category')
                ->get();
            if (!count($item)) {
                continue;
            }
            $items[] = ['name' => $wh->name, 'data' => $item];
        }
        return $items;
    }

    /**
     * materialStockMain
     *
     * @return void
     */
    private function materialStockMain()
    {
        $items = [];
        foreach ($this->getWH('main') as $wh) {
            $item = MaterialStock::where('project_id', '!=', 'dummy-001')
                ->selectRaw("SUM(material_stock_details.good_stock) as qty, categories.name as category")
                ->leftJoin('material_stock_details', 'material_stock_details.material_stock_id', '=', 'material_stocks.id')
                ->leftJoin('materials', 'materials.id', '=', 'material_stocks.material_id')
                ->leftJoin('categories', 'categories.id', '=', 'materials.category_id')
                ->where('warehouse_id', $wh->id)
                ->when($this->company_id, function ($q) {
                    $q->join('projects', 'projects.id', '=', 'material_stocks.project_id')
                        ->whereRaw("projects.company_id ='" . $this->company_id . "'");
                })
                ->groupBy('category')
                ->get();
            if (!count($item)) {
                continue;
            }
            $items[] = ['name' => $wh->name, 'data' => $item];
        }
        foreach ($this->getWH('transit') as $wh) {
            $item = MaterialStock::where('project_id', '!=', 'dummy-001')
                ->selectRaw("SUM(material_stock_details.good_stock) as qty, categories.name as category")
                ->leftJoin('material_stock_details', 'material_stock_details.material_stock_id', '=', 'material_stocks.id')
                ->leftJoin('materials', 'materials.id', '=', 'material_stocks.material_id')
                ->leftJoin('categories', 'categories.id', '=', 'materials.category_id')
                ->where('warehouse_id', $wh->id)
                ->when($this->company_id, function ($q) {
                    $q->join('projects', 'projects.id', '=', 'material_stocks.project_id')
                        ->whereRaw("projects.company_id ='" . $this->company_id . "'");
                })
                ->groupBy('category')
                ->get();
            if (!count($item)) {
                continue;
            }
            $items[] = ['name' => $wh->name, 'data' => $item];
        }
        return $items;
    }

    /**
     * OutVsStock
     *
     * @return void
     */
    private function OutVsStock()
    {
        $items = [];
        foreach (Category::where('status', 1)->get() as $cat) {
            $stock = MaterialStock::where('project_id', '!=', 'dummy-001')
                ->selectRaw("SUM(material_stock_details.good_stock) as qty")
                ->leftJoin('material_stock_details', 'material_stock_details.material_stock_id', '=', 'material_stocks.id')
                ->leftJoin('materials', 'materials.id', '=', 'material_stocks.material_id')
                ->leftJoin('categories', 'categories.id', '=', 'materials.category_id')
                ->whereRaw("categories.id = '" . $cat->id . "'")
                ->when($this->company_id, function ($q) {
                    $q->join('projects', 'projects.id', '=', 'material_stocks.project_id')
                        ->whereRaw("projects.company_id ='" . $this->company_id . "'");
                })
                ->pluck('qty')->first();

            $out = MaterialStockHistory::whereBetween('transaction_date', [$this->from, $this->to])
                ->where('project_id', '!=', 'dummy-001')
                ->selectRaw("ABS(SUM(good_qty)) as qty")
                ->leftJoin('materials', 'materials.id', '=', 'material_stock_histories.material_id')
                ->leftJoin('categories', 'categories.id', '=', 'materials.category_id')
                ->whereRaw("categories.id = '" . $cat->id . "'")
                ->where('good_qty', '<', 0)
                ->whereIn('source_type', ['material-to-site', 'stock-opname'])
                ->when($this->company_id, function ($q) {
                    $q->join('projects', 'projects.id', '=', 'material_stock_histories.project_id')
                        ->whereRaw("projects.company_id ='" . $this->company_id . "'");
                })
                ->pluck('qty')->first();

            $items[] = [
                'name' => $cat->name,
                'data' => [
                    'stock' => $stock,
                    'out' => $out
                ]
            ];
        }
        return $items;
    }

    /**
     * materialBadLostStock
     *
     * @return void
     */
    private function materialBadLostStock()
    {
        $items = [];
        foreach ($this->getWH() as $wh) {
            $item = MaterialStock::selectRaw("SUM(material_stock_details.bad_stock) as bad_qty, SUM(material_stock_details.lost_stock) as lost_qty")
                ->leftJoin('material_stock_details', 'material_stock_details.material_stock_id', '=', 'material_stocks.id')
                ->leftJoin('materials', 'materials.id', '=', 'material_stocks.material_id')
                ->leftJoin('categories', 'categories.id', '=', 'materials.category_id')
                ->where('warehouse_id', $wh->id)
                ->when($this->company_id, function ($q) {
                    $q->join('projects', 'projects.id', '=', 'material_stocks.project_id')
                        ->whereRaw("projects.company_id ='" . $this->company_id . "'");
                })
                ->first();

            $items[] = [
                'name' => $wh->name,
                'data' => [
                    'bad' => $item->bad_qty ?? null,
                    'lost' => $item->lost_qty ?? null,
                ]
            ];
        }
        return $items;
    }
}
