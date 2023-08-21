<?php

namespace App\Models;

class TransferDetail extends BaseModel
{
    /**
     * guarded
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * supplier_inbound
     *
     * @return void
     */
    public function transfer()
    {
        return $this->belongsTo(Transfer::class, 'transfer_id');
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

    /**
     * stocks
     *
     * @return void
     */
    public function stocks()
    {
        return $this->hasMany(TransferDetailStock::class, 'transfer_detail_id', 'id');
    }
}
