<?php

namespace App\Models;

class MaterialDisposalDetail extends BaseModel
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
    public function material_disposal()
    {
        return $this->belongsTo(MaterialDisposal::class);
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
