<?php

namespace App\Models;

class MaterialToSiteDetailStock extends BaseModel
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
    public function material_to_site_detail()
    {
        return $this->belongsTo(MaterialToSiteDetail::class);
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
}
