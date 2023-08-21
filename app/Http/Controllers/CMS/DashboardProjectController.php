<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MaterialStock;
use App\Models\MaterialStockDetail;
use App\Models\MaterialStockHistory;
use App\Models\MaterialToSiteDetailStock;
use App\Models\Project;
use App\Models\PurchaseOrderDetail;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DashboardProjectController extends Controller
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
     * index
     *
     * @return void
     */
    public function index()
    {

        $data['count']['project'] = Project::selectRaw("COUNT(id) as cnt")
            ->where('status', 1)
            ->when($this->company_id, function ($q) {
                $q->where('company_id', $this->company_id);
            })
            ->pluck('cnt')->first();
        $data['count']['main_warehouse'] = Warehouse::selectRaw("COUNT(id) as cnt")
            ->where('status', 1)
            ->where('type', 'main')
            ->pluck('cnt')->first();
        $data['count']['transit_warehouse'] = Warehouse::selectRaw("COUNT(id) as cnt")
            ->where('status', 1)
            ->where('type', 'transit')
            ->pluck('cnt')->first();
        $data['count']['lastmile_warehouse'] = Warehouse::selectRaw("COUNT(id) as cnt")
            ->where('status', 1)
            ->where('type', 'lastmile')
            ->pluck('cnt')->first();

        $data['out'] = $this->getOutData();
        $data['stock'] = $this->getStockData();
        $data['transaction'] = $this->getTransactionData();

        $r = ['status' => Response::HTTP_OK, 'result' => $data];
        return response()->json($r, Response::HTTP_OK);
    }

    /**
     * getCategory
     *
     * @return mixed
     */
    private function getCategory()
    {
        return Category::where('status', 1)
            ->with(['materials' => function ($q) {
                $q->select(['category_id', 'uom']);
            }])
            ->orderBy('name', 'ASC')
            ->get();
    }

    /**
     * getOutData
     *
     * @return void
     */
    private function getOutData()
    {
        $items = [];
        foreach ($this->getCategory() as $cat) {
            $stockOut = MaterialStockHistory::whereBetween('transaction_date', [$this->from, $this->to])
                ->where('project_id', '!=', 'dummy-001')
                ->selectRaw("ABS(SUM(good_qty)) as qty, projects.name as project")
                ->join('projects', 'projects.id', '=', 'material_stock_histories.project_id')
                ->whereHas('material', function ($q) use ($cat) {
                    $q->where('category_id', $cat->id);
                })
                ->where('good_qty', '<', 0)
                ->whereIn('source_type', ['material-to-site', 'stock-opname'])
                ->when($this->company_id, function ($q) {
                    $q->whereRaw("projects.company_id ='" . $this->company_id . "'");
                })
                ->groupBy('project')
                ->get();

            if (!count($stockOut)) {
                continue;
            }
            $uom = count($cat->materials) ? $cat->materials[0]->uom : '-';
            $items[] = ['name' => $cat->name, 'uom' => $uom,  'data' => $stockOut];
        }
        return $items;
    }

    /**
     * getStockData
     *
     * @return void
     */
    private function getStockData()
    {
        $items = [];
        foreach ($this->getCategory() as $cat) {
            $stock = MaterialStock::where('project_id', '!=', 'dummy-001')
                ->selectRaw("SUM(material_stock_details.good_stock) as qty, projects.name as project")
                ->join('projects', 'projects.id', '=', 'material_stocks.project_id')
                ->leftJoin('material_stock_details', 'material_stock_details.material_stock_id', '=', 'material_stocks.id')
                ->whereHas('material', function ($q) use ($cat) {
                    $q->where('category_id', $cat->id);
                })
                ->when($this->company_id, function ($q) {
                    $q->whereRaw("projects.company_id ='" . $this->company_id . "'");
                })
                ->groupBy('project')
                ->get();
            if (!count($stock)) {
                continue;
            }
            $uom = count($cat->materials) ? $cat->materials[0]->uom : '-';
            $items[] = ['name' => $cat->name, 'uom' => $uom,  'data' => $stock];
        }
        return $items;
    }

    /**
     * getTransactionData
     *
     * @return void
     */
    private function getTransactionData()
    {
        return MaterialStockHistory::whereBetween('transaction_date', [$this->from, $this->to])
            ->where('project_id', '!=', 'dummy-001')
            ->selectRaw("ABS(SUM(good_qty)) as qty, projects.name as project")
            ->join('projects', 'projects.id', '=', 'material_stock_histories.project_id')
            ->where('source_type', 'material-to-site')
            ->when($this->company_id, function ($q) {
                $q->whereHas('project', function ($q) {
                    $q->where('company_id', $this->company_id);
                });
            })
            ->groupBy('project')
            ->orderBy('qty','DESC')
            ->get();
    }
}
