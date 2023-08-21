<?php

namespace App\Exports;

use App\Models\Warehouse;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class WarehouseExport implements
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
    protected $req;

    /**
     * type
     *
     * @var mixed
     */
    protected $type;

    /**
     * __construct
     *
     * @param  mixed $this->request
     * @return void
     */
    public function __construct($req, $type)
    {
        $this->req = $req;
        $this->type = $type;
    }

    /**
     * query
     *
     * @return void
     */
    public function query()
    {
        $items = Warehouse::query();
        $items->where('type', $this->type);
        $items->orderBy('code', 'asc');
        $q = (isset($this->req->q) && $this->req->q == 'null') ? null : $this->req->q;
        if (isset($q) && $q) {
            $items->where(function ($query) use ($q) {
                $query->orWhere('name', 'like', '%' . $q . '%')
                    ->orWhere('description', 'like', '%' . $q . '%');
            });
        }
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
            $row->code,
            $row->name,
            $row->pic_name,
            $row->pic_phone,
            $row->address,
            $row->status,
            Carbon::parse($row->created_at)->format('Y-m-d H:i:s')
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
            'Code',
            'Name',
            'PIC Name',
            'PIC Phone Number',
            'Address',
            'Status',
            'Created at'
        ];
    }
}
