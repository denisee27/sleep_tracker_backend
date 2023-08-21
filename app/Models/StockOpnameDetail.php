<?php

namespace App\Models;

class StockOpnameDetail extends BaseModel
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
    public function stock_opname()
    {
        return $this->belongsTo(StockOpname::class);
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
