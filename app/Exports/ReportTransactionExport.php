<?php

namespace App\Exports;

use App\Models\MaterialStockHistory;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class ReportTransactionExport implements
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
        $items = MaterialStockHistory::query();
        $items->whereBetween('transaction_date', [$this->data->from_date, $this->data->to_date]);
        $items->where('project_id', '!=', 'dummy-001');
        if (isset($this->data->warehouse_type) && $this->data->warehouse_type) {
            $items->whereHas('warehouse', function ($q) {
                $q->where('type', $this->data->warehouse_type);
            });
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
            'warehouse:id,code,name,type',
            'project:id,code,name',
            'material_to_site:id,ticket_number,from_warehouse'
        ]);
        $items->orderBy('transaction_date', 'ASC');
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
        $to = $row->warehouse->code;
        if ($row->source_type == 'material-to-site') {
            $to = 'USER';
        } elseif ($row->source_type == 'transfer-project-code') {
            $to = $row->project->code;
        }
        return [
            $row->transaction_date,
            $row->material->name,
            $row->material->number,
            $row->project->name,
            $row->project->code,
            $row->warehouse->code,
            ucwords(str_replace('-', ' ', $row->source_type)),
            $to,
            (isset($row->material_to_site->ticket_number) ? $row->material_to_site->ticket_number : ''),
            ($row->good_qty > 0 ? $row->good_qty : ''),
            ($row->good_qty < 0 ? abs($row->good_qty) : ''),
            $row->source_number
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
            'Date',
            'Material Name',
            'Material Number',
            'Project Name',
            'Project Code',
            'WH-ID',
            'Status',
            'To/From',
            'TT Number',
            'In',
            'Out',
            'Transaction ID'
        ];
    }
}
