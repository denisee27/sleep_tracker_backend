<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\MaterialStockDetail;
use App\Models\MaterialStockHistory;
use App\Models\MaterialToSiteDetailStock;
use App\Models\PurchaseOrderDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DashboardFinanceController extends Controller
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
     * @param  mixed $request
     * @return void
     */
    public function index(Request $request)
    {
        if (isset($request->topOnly)) {
            goto topOnly;
        }
        $data['count']['in'] = PurchaseOrderDetail::whereHas('purchase_order', function ($q) {
            $q->whereHas('supplier_inbound', function ($q) {
                $q->where('status', 1)
                    ->whereBetween('inbound_date', [$this->from, $this->to]);
            })->when($this->company_id, function ($q) {
                $q->where('company_id', $this->company_id);
            });
        })->selectRaw("SUM(idr_price*qty) as idr, SUM(qty) as unit")
            ->first();

        $out_data = MaterialToSiteDetailStock::whereHas('material_to_site_detail', function ($q) {
            $q->whereHas('material_to_site', function ($q) {
                $q->where('status', 1)
                    ->whereBetween('request_date', [$this->from, $this->to]);
            });
        })->select(['id', 'material_to_site_detail_id', 'material_stock_detail_id', 'qty'])
            ->when($this->company_id, function ($q) {
                $q->whereHas('material_stock_detail', function ($q) {
                    $q->whereHas('purchase_order', function ($q) {
                        $q->where('company_id', $this->company_id);
                    });
                });
            })->with([
                'material_to_site_detail:id,material_id',
                'material_stock_detail:id,purchase_order_id',
                'material_stock_detail.purchase_order:id',
                'material_stock_detail.purchase_order.details:id,purchase_order_id,material_id,idr_price'
            ])->get()->map(function ($i) {
                $po_detail = $i->material_stock_detail->purchase_order->details->filter(function ($e) use ($i) {
                    return $e->material_id == $i->material_to_site_detail->material_id;
                })->first();
                return ['idr_price' => $po_detail->idr_price, 'qty' => ((int)$i->qty)];
            });

        $data['count']['out']['idr'] = $out_data->sum(function ($i) {
            return $i['idr_price'] * $i['qty'];
        });
        $data['count']['out']['unit'] = $out_data->sum('qty');

        $get_total = $this->get_total_query()->get();
        $total_data = $get_total->map(function ($i) {
            $po_detail = $i->purchase_order->details->filter(function ($e) use ($i) {
                return $e->material_id == $i->material_stock->material_id;
            })->first();
            return ['idr_price' => $po_detail->idr_price, 'qty' => $i->good_stock];
        });

        $data['count']['total']['idr'] = $total_data->sum(function ($i) {
            return $i['idr_price'] * $i['qty'];
        });
        $data['count']['total']['unit'] = $total_data->sum('qty');
        topOnly:
        $data['top']['items'] = $this->get_top_material($request->orderBy ?? 'amount');
        $data['top']['pie'] = $this->get_pie_data($request->orderBy ?? 'amount');
        $data['top']['bar'] = $this->get_bar_data();
        $r = ['status' => Response::HTTP_OK, 'result' => $data];
        return response()->json($r, Response::HTTP_OK);
    }

    /**
     * get_pie_data
     *
     * @return void
     */
    private function get_pie_data($orderBy = 'amount')
    {
        $query = $this->get_total_query();
        $query->with(['material_stock.warehouse:id,type']);
        $query->whereHas('purchase_order', function ($q) {
            $q->whereHas('supplier_inbound', function ($q) {
                $q->where('status', 1)
                    ->whereBetween('inbound_date', [$this->from, $this->to]);
            });
        });
        $items = $query->get()->map(function ($i, $x) {
            $po_detail = $i->purchase_order->details->filter(function ($e) use ($i) {
                return $e->material_id == $i->material_stock->material_id;
            })->first();
            return ['type' => $i->material_stock->warehouse->type, 'amount' => $po_detail->idr_price * $i->good_stock, 'qty' => $i->good_stock];
        })->groupBy('type')->map(function ($e) {
            $firstRow = $e->first();
            return [
                'type' => $firstRow['type'],
                'qty' => $e->sum('qty'),
                'amount' => $e->sum('amount')
            ];
        })->values()
            ->sortByDesc($orderBy)
            ->values()
            ->take(10)
            ->all();
        return $items;
    }

    /**
     * get_bar_data
     *
     * @return void
     */
    private function get_bar_data()
    {
        $items = [];
        $data = $this->get_top_material('qty');
        foreach ($data as $item) {
            $all_stock = MaterialStockHistory::where('material_id', $item['id'])
                ->whereBetween('transaction_date', [$this->from, $this->to])
                ->where('project_id', '!=', 'dummy-001')
                ->whereIn('source_type', ['supplier-inbound', 'material-to-site', 'stock-opname'])
                ->selectRaw("SUM(good_qty) as qty")
                ->when($this->company_id, function ($q) {
                    $q->whereHas('project', function ($q) {
                        $q->where('company_id', $this->company_id);
                    });
                })
                ->get()
                ->pluck('qty')
                ->first();

            $in_stock = MaterialStockHistory::where('material_id', $item['id'])
                ->whereBetween('transaction_date', [$this->from, $this->to])
                ->where('project_id', '!=', 'dummy-001')
                ->where('good_qty', '>', 0)
                ->selectRaw("SUM(good_qty) as qty")
                ->whereIn('source_type', ['supplier-inbound', 'material-to-site', 'stock-opname'])
                ->when($this->company_id, function ($q) {
                    $q->whereHas('project', function ($q) {
                        $q->where('company_id', $this->company_id);
                    });
                })
                ->get()
                ->pluck('qty')
                ->first();

            $out_stock = MaterialStockHistory::where('material_id', $item['id'])
                ->whereBetween('transaction_date', [$this->from, $this->to])
                ->where('project_id', '!=', 'dummy-001')
                ->selectRaw("ABS(SUM(good_qty)) as qty")
                ->where('good_qty', '<', 0)
                ->whereIn('source_type', ['material-to-site', 'stock-opname'])
                ->when($this->company_id, function ($q) {
                    $q->whereHas('project', function ($q) {
                        $q->where('company_id', $this->company_id);
                    });
                })
                ->get()
                ->pluck('qty')
                ->first();

            $begin = $all_stock - $in_stock + $out_stock;
            $items[] = [
                'all_stock' => $all_stock,
                'name' => $item['name'],
                'stock' => $all_stock,
                'begin' => $begin > 0 ? $begin : 0,
                'in_stock' => $in_stock,
                'out_stock' => $out_stock
            ];
        }
        return $items;
    }

    /**
     * get_top_material
     *
     * @param  mixed $orderBy
     * @return mixed
     */
    private function get_top_material($orderBy = 'amount')
    {
        $query = $this->get_total_query();
        $query->with(['material_stock.warehouse:id,type']);
        $query->whereHas('purchase_order', function ($q) {
            $q->whereHas('supplier_inbound', function ($q) {
                $q->where('status', 1)
                    ->whereBetween('inbound_date', [$this->from, $this->to]);
            });
        });
        $items = $query->get()->map(function ($i) {
            $po_detail = $i->purchase_order->details->filter(function ($e) use ($i) {
                return $e->material_id == $i->material_stock->material_id;
            })->first();
            return ['material_id' => $i->material_stock->material_id, 'material_name' => $i->material_stock->material->name, 'amount' => $po_detail->idr_price * $i->good_stock, 'qty' => $i->good_stock];
        })->groupBy('material_id')->map(function ($e) {
            $firstRow = $e->first();
            return [
                'id' => $firstRow['material_id'],
                'qty' => $e->sum('qty'),
                'amount' => $e->sum('amount'),
                'name' => $firstRow['material_name']
            ];
        })->values()
            ->sortByDesc($orderBy)
            ->values()
            ->take(10)
            ->all();
        return $items;
    }

    /**
     * get_total_query
     *
     * @return mixed
     */
    private function get_total_query()
    {
        $total_query = MaterialStockDetail::query();
        $total_query->select(['material_stock_id', 'purchase_order_id', 'good_stock']);
        $total_query->where('purchase_order_id', '!=', 'dummy-001');
        $total_query->with([
            'material_stock:id,material_id',
            'purchase_order:id',
            'purchase_order.details:purchase_order_id,material_id,idr_price'
        ])->when($this->company_id, function ($q) {
            $q->whereHas('purchase_order', function ($q) {
                $q->where('company_id', $this->company_id);
            });
        });
        return $total_query;
    }
}
