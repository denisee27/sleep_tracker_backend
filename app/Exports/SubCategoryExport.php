<?php

namespace App\Exports;

use App\Models\SubCategory;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class SubCategoryExport implements
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
        $items = SubCategory::query();
        $items->orderBy('code', 'asc');
        $q = (isset($this->req->q) && $this->req->q == 'null') ? null : $this->req->q;
        if (isset($q) && $q) {
            $items->where(function ($query) use ($q) {
                $query->orWhere('name', 'like', '%' . $q . '%')
                    ->orWhere('code', 'like', '%' . $q . '%')
                    ->orWhere('description', 'like', '%' . $q . '%')
                    ->orWhereHas('category', function ($query) use ($q) {
                        $query->where('name', 'like', '%' . $q . '%')
                            ->orWhere('code', 'like', '%' . $q . '%');
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
            $row->category->code,
            $row->category->name,
            $row->code,
            $row->name,
            $row->description,
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
            'Category Code',
            'Category Name',
            'SubCategory Code',
            'SubCategory Name',
            'Description',
            'Status',
            'Created at'
        ];
    }
}
