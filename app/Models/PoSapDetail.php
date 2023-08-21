<?php

namespace App\Models;

class PoSapDetail extends BaseModel
{
    /**
     * po_sap
     *
     * @return void
     */
    public function po_sap()
    {
        return $this->belongsTo(PoSap::class);
    }
}
