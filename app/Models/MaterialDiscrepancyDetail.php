<?php

namespace App\Models;

class MaterialDiscrepancyDetail extends BaseModel
{
    /**
     * guarded
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * detail
     *
     * @return void
     */
    public function material_discrepancy()
    {
        return $this->belongsTo(MaterialDiscrepancy::class);
    }

    /**
     * material_stock
     *
     * @return void
     */
    public function material_stock_detail()
    {
        return $this->belongsTo(MaterialStockDetail::class);
    }

    /**
     * material_stock
     *
     * @return void
     */
    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
