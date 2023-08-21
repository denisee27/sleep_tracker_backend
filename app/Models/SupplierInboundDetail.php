<?php

namespace App\Models;

class SupplierInboundDetail extends BaseModel
{
    /**
     * supplier_inbound
     *
     * @return void
     */
    public function supplier_inbound()
    {
        return $this->belongsTo(SupplierInbound::class);
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
