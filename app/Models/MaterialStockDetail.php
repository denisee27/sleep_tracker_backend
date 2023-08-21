<?php

namespace App\Models;

class MaterialStockDetail extends BaseModel
{
    /**
     * guarded
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * purchase_order
     *
     * @return void
     */
    public function purchase_order()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * material_stock
     *
     * @return void
     */
    public function material_stock()
    {
        return $this->belongsTo(MaterialStock::class);
    }
}
