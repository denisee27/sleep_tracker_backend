<?php

namespace App\Exports;

use App\Models\MaterialToSiteDetailPhoto;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class ReportSiteExport implements
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
        $items = MaterialToSiteDetailPhoto::query();
        $items->whereHas('material_to_site_detail', function ($q) {
            $q->whereHas('material_to_site', function ($q) {
                $q->whereBetween('request_date', [$this->data->from_date, $this->data->to_date])
                    ->when((isset($this->data->warehouse_type) && $this->data->warehouse_type), function ($q) {
                        $q->whereHas('from_warehouse', function ($q) {
                            $q->where('type', $this->data->warehouse_type);
                        });
                    })->when((isset($this->data->warehouses) && $this->data->warehouses), function ($q) {
                        $wh_id = explode(',', $this->data->warehouses);
                        $q->whereIn('from_warehouse', $wh_id);
                    })->when((isset($this->data->projects) && $this->data->projects), function ($q) {
                        $pr_id = explode(',', $this->data->projects);
                        $q->whereIn('project_id', $pr_id);
                    });
            });
        });
        $items->with([
            'material_to_site_detail:id,material_to_site_id,material_id',
            'material_to_site_detail.material:id,number,name',
            'material_to_site_detail.material_to_site:id,project_id,from_warehouse,request_date,number,section_name',
            'material_to_site_detail.material_to_site.warehouse:id,name',
            'material_to_site_detail.material_to_site.project:id,code,name'
        ]);
        $items->orderBy('created_at', 'ASC');
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
            $row->material_to_site_detail->material_to_site->request_date,
            $row->material_to_site_detail->material_to_site->warehouse->name,
            $row->material_to_site_detail->material_to_site->number,
            $row->material_to_site_detail->material->number,
            $row->material_to_site_detail->material->name,
            $row->material_to_site_detail->material_to_site->project->name,
            $row->material_to_site_detail->material_to_site->project->code,
            $row->material_to_site_detail->material_to_site->section_name,
            $row->longitude,
            $row->latitude,
            config('app.asset_url') . '/' . $row->photo,
            'https://maps.google.com/?q=' . $row->latitude . ',' . $row->longitude
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
            'Warehouse',
            'Transaction ID',
            'Material Number',
            'Material Name',
            'Project Name',
            'Project Code',
            'Section Name',
            'Longitude',
            'Latitude',
            'Photo URL',
            'Google Maps URL'
        ];
    }
}
