<?php

namespace App\Exports;

use App\Models\Material;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class MaterialExport implements
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
     * __construct
     *
     * @param  mixed $this->request
     * @return void
     */
    public function __construct($req)
    {
        $this->req = $req;
    }

    /**
     * query
     *
     * @return void
     */
    public function query()
    {
        $items = Material::query();
        $items->orderBy('number', 'asc');
        $items->with(['category:id,name']);
        $q = (isset($this->req->q) && $this->req->q == 'null') ? null : $this->req->q;
        if (isset($q) && $q) {
            $items->where(function ($query) use ($q) {
                $regex = str_replace(' ', '|', $q);
                $query->orWhere('number', 'like', '%' . $q . '%')
                    ->orWhere('name', 'rlike', $regex)
                    ->orWhere('description', 'like', '%' . $q . '%')
                    ->orWhereHas('category', function ($query) use ($q) {
                        $query->where('name', 'like', '%' . $q . '%');
                    });
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
            $row->number,
            $row->name,
            $row->description,
            $row->uom,
            ($row->category->name ?? null),
            (isset($row->minimum_stock['main']) ? $row->minimum_stock['main'] : null),
            (isset($row->minimum_stock['transit']) ? $row->minimum_stock['transit'] : null),
            (isset($row->minimum_stock['lastmile']) ? $row->minimum_stock['lastmile'] : null),
            $row->status,
            $row->is_fifo,
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
            'Number',
            'Name',
            'Description',
            'UoM',
            'Category Name',
            'Min. Stock in Main',
            'Min. Stock in Transit',
            'Min. Stock in Lastmile',
            'Status',
            'Is FIFO',
            'Created at'
        ];
    }
}
