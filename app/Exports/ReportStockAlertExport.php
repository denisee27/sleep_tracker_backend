<?php

namespace App\Exports;

use App\Models\Material;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class ReportStockAlertExport implements
    FromCollection,
    WithMapping,
    WithHeadings,
    WithCustomChunkSize,
    ShouldAutoSize,
    WithStrictNullComparison
{
    /**
     * request
     *
     * @var mixed
     */
    protected $data;

    /**
     * __construct
     *
     * @param  mixed $this->request
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $items = Material::query();
        $items->where('status', 1);
        $items->select(['id', 'number', 'name', 'uom', 'minimum_stock']);
        $items->whereHas('stocks');
        $items->with([
            'stocks' => function ($q) {
                $q->where('project_id', '!=', 'dummy-001')
                    ->whereHas('warehouse', function ($q) {
                        $q->where('type', $this->data->warehouse_type);
                    })->when(isset($this->data->warehouses) && count($this->data->warehouses), function ($q) {
                        $q->whereIn('warehouse_id', $this->data->warehouses);
                    })
                    ->withSum('details as stock', 'good_stock')
                    ->with(['warehouse:id,name']);
            }
        ]);
        return $items->get()->map(function ($i) {
            $i->total_stock = collect($i->stocks)->sum('stock');
            $i->warehouse = collect($i->stocks)->groupBy('warehouse_id')->map(function ($e) {
                $firstRow = $e->first();
                return [
                    'id' => $firstRow->warehouse_id,
                    'stock' => $e->sum('stock'),
                    'name' => $firstRow->warehouse->name
                ];
            })->values()->filter(function ($e) use ($i) {
                if (!isset($i->minimum_stock[$this->data->warehouse_type])) {
                    return false;
                }
                return $e['stock'] < ((float)$i->minimum_stock[$this->data->warehouse_type]);
            })->all();
            return $i;
        })->filter(function ($i) {
            return collect($i->warehouse)->filter(function ($e) use ($i) {
                return $e['stock'] < ((float)$i->minimum_stock[$this->data->warehouse_type]);
            })->count() > 0;
        });
    }

    /**
     * map
     *
     * @param  mixed $row
     * @return array
     */
    public function map($row): array
    {
        $items = [];
        foreach ($row->warehouse as $item) {
            $items[] = [
                $item['name'],
                $row['number'],
                $row['name'],
                $row['minimum_stock'][$this->data->warehouse_type],
                $item['stock'],
                $row['uom']
            ];
        }
        return $items;
    }

    /**
     * chunkSize
     *
     * @return int
     */
    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * headings
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'Warehouse Name',
            'Material Number',
            'Material Name',
            'Minimun Stock In ' . $this->data->warehouse_type . ' WH',
            'Current Stock',
            'UoM'
        ];
    }
}
