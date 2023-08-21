<?php

namespace App\Models;

class TransferProjectCodeDetail extends BaseModel
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
    public function transfer_project_code()
    {
        return $this->belongsTo(TransferProjectCode::class, 'transfer_project_code_id');
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
        return $this->hasMany(TransferProjectCodeDetailStock::class, 'transfer_project_code_detail_id', 'id');
    }
}
