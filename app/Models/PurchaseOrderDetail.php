<?php

namespace App\Models;

class PurchaseOrderDetail extends BaseModel
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
     * material
     *
     * @return void
     */
    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
