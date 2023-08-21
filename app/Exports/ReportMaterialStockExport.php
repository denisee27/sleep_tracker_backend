<?php

namespace App\Exports;

use App\Models\MaterialStock;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class ReportMaterialStockExport implements
    FromQuery,
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

    /**
     * query
     *
     * @return void
     */
    public function query()
    {
        $items = MaterialStock::query();
        $items->where('project_id', '!=', 'dummy-001');
        $items->selectRaw("material_stocks.*");
        if (isset($this->data->warehouse_type) && $this->data->warehouse_type) {
            $items->where('warehouses.type', $this->data->warehouse_type);
        }
        if (isset($this->data->warehouses) && $this->data->warehouses) {
            $wh_id = explode(',', $this->data->warehouses);
            $items->whereIn('warehouse_id', $wh_id);
        }
        if (isset($this->data->projects) && $this->data->projects) {
            $pr_id = explode(',', $this->data->projects);
            $items->whereIn('project_id', $pr_id);
        }
        $items->with([
            'material:id,number,name,uom',
            'warehouse:id,name,type',
            'project:id,code'
        ]);
        $items->withSum('details as current_stock', $this->data->type . '_stock');
        $items->join('warehouses', 'material_stocks.warehouse_id', '=', 'warehouses.id');
        $items->orderBy('warehouses.type', 'ASC');
        $items->orderBy('warehouses.name', 'ASC');
        return $items;
    }

    /**
     * map
     *
     * @param  mixed $row
     * @return array
     */
    public function map($row): array
    {
        return [
            $row->warehouse->type,
            $row->warehouse->name,
            $row->project->code,
            $row->material->number,
            $row->material->name,
            $row->current_stock,
            $row->material->uom
        ];
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
            'Warehouse Type',
            'Warehouse Name',
            'Project Code',
            'Material Number',
            'Material Name',
            ucwords($this->data->type) . ' Stock',
            'UoM'
        ];
    }
}
