<?php

namespace App\Exports;

use App\Models\MaterialDisposalDetail;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class MaterialDisposalItemExport implements
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
    protected $id;

    /**
     * __construct
     *
     * @param  mixed $this->request
     * @return void
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * query
     *
     * @return void
     */
    public function query()
    {
        $items = MaterialDisposalDetail::query();
        $items->where('material_disposal_id', $this->id);
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
        $po = $row->material_stock_detail->purchase_order->details()->where('material_id', $row->material_stock_detail->material_stock->material_id)->first();
        return [
            $row->material_stock_detail->material_stock->project->code,
            $row->material_stock_detail->purchase_order->number,
            $row->material_stock_detail->purchase_order->delivery_date,
            $row->material_stock_detail->material_stock->material->number,
            $row->material_stock_detail->material_stock->material->name,
            $row->material_stock_detail->material_stock->material->uom,
            $po->idr_price ?? 'N/A',
            $po->currency ?? 'N/A',
            $row->system_bad_qty,
            $row->disposed_bad_qty
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
            'Project Code',
            'PO Number',
            'Delivery Date',
            'Material Number',
            'Material Name',
            'UoM',
            'Unit Price',
            'Currency',
            'System Bad Stock',
            'Disposed Bad Stock'
        ];
    }
}
