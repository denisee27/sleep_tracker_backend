<?php

namespace App\Exports;

use App\Models\StockOpnameDetail;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockOpnameTemplateExport implements
    FromQuery,
    WithMapping,
    WithHeadings,
    WithCustomChunkSize,
    ShouldAutoSize,
    WithStrictNullComparison,
    WithStyles,
    WithEvents
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
     * registerEvents
     *
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->getSheet()->getDelegate()->getColumnDimension('A')->setVisible(false);
            }
        ];
    }

    /**
     * query
     *
     * @return void
     */
    public function query()
    {
        $items = StockOpnameDetail::query();
        $items->where('stock_opname_id', $this->id);
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
            $row->id,
            $row->material_stock_detail->material_stock->project->code,
            $row->material_stock_detail->purchase_order->number,
            $row->material_stock_detail->purchase_order->delivery_date,
            $row->material_stock_detail->material_stock->material->number,
            $row->material_stock_detail->material_stock->material->name,
            $row->material_stock_detail->material_stock->material->uom,
            $row->system_good_qty,
            $row->system_bad_qty,
            $row->system_lost_qty,
            null,
            null,
            null,
            null
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
            'id',
            'Project Code',
            'PO Number',
            'Delivery Date',
            'Material Number',
            'Material Name',
            'UoM',
            'System Good Qty',
            'System Bad Qty',
            'System Lost Qty',
            'Counted Good Qty',
            'Counted Bad Qty',
            'Counted Lost Qty',
            'Notes'
        ];
    }

    /**
     * styles
     *
     * @param  Worksheet $sheet
     * @return void
     */
    public function styles(Worksheet $sheet)
    {
        $sheet->getProtection()->setPassword(mt_rand(0, 999));
        $sheet->getProtection()->setSheet(true);
        $sheet->getStyle('K2:N1000')->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);
        $sheet->getStyle('B1');
    }
}
