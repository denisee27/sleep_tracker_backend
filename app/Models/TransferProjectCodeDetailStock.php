<?php

namespace App\Models;

class TransferProjectCodeDetailStock extends BaseModel
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
    public function transfer_project_code_detail()
    {
        return $this->belongsTo(TransferProjectCodeDetail::class);
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
