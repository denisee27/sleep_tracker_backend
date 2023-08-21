<?php

namespace App\Console\Commands;

use App\Mail\StockAlertMail;
use App\Models\Material;
use App\Models\StockAlertNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class StockAlertCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alert:stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stock alert email notification';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $notifications = StockAlertNotification::where('status', 1)
            ->with([
                'warehouses',
                'warehouses.warehouse:id,name,type',
                'users' => function ($q) {
                    $q->whereHas('user', function ($q) {
                        $q->where('status', 1);
                    })->with(['user' => function ($q) {
                        $q->select(['id', 'name', 'email'])
                            ->whereNotNull('email');
                    }]);
                }
            ])->get();
        foreach ($notifications as $notif) {
            if (!count($notif->users) || !count($notif->warehouses)) {
                continue;
            }
            $wh_ids = $notif->warehouses->pluck('warehouse_id');
            $wh_types = $notif->warehouses->map(function ($i) {
                return $i->warehouse->type;
            })->unique()->values();
            $alert = [];
            foreach ($wh_types as $wh) {
                $alert[] = [
                    'type' => $wh,
                    'items' => $this->getAlert($wh_ids, $wh)
                ];
            }
            $data['datas'] = $alert;
            $data['subject'] = 'IMS - Stock Alert';
            foreach ($notif->users as $user) {
                $data['name'] = $user->user->name;
                try {
                    $mail = new StockAlertMail($data);
                    Mail::to($user->user->email)->send($mail);
                } catch (\Throwable $e) {
                    throw $e;
                }
            }
        }
    }

    /**
     * getAlert
     *
     * @param  mixed $warehouse_ids
     * @return mixed
     */
    private function getAlert($warehouse_ids, $wh_type)
    {
        $items = Material::query();
        $items->where('status', 1);
        $items->select(['id', 'number', 'name', 'uom', 'minimum_stock']);
        $items->whereHas('stocks');
        $items->with([
            'stocks' => function ($q) use ($warehouse_ids, $wh_type) {
                $q->where('project_id', '!=', 'dummy-001')
                    ->whereHas('warehouse', function ($q) use ($wh_type) {
                        $q->where('type', $wh_type);
                    })
                    ->whereIn('warehouse_id', $warehouse_ids)
                    ->withSum('details as stock', 'good_stock')
                    ->with(['warehouse:id,name']);
            }
        ]);
        return $items->get()->map(function ($i) use ($wh_type) {
            $i->total_stock = collect($i->stocks)->sum('stock');
            $i->warehouse = collect($i->stocks)->groupBy('warehouse_id')->map(function ($e) {
                $firstRow = $e->first();
                return [
                    'id' => $firstRow->warehouse_id,
                    'stock' => $e->sum('stock'),
                    'name' => $firstRow->warehouse->name
                ];
            })->values()->filter(function ($e) use ($i, $wh_type) {
                return $e['stock'] < (float)($i->minimum_stock[$wh_type] ?? 0);
            })->all();
            return $i;
        })->filter(function ($i) use ($wh_type) {
            return collect($i->warehouse)->filter(function ($e) use ($i, $wh_type) {
                return $e['stock'] < (float)($i->minimum_stock[$wh_type] ?? 0);
            })->count() > 0;
        });
    }
}
