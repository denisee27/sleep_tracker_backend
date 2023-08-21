<?php

namespace App\Models;

class UsedMaterialReturnDetail extends BaseModel
{
    /**
     * guarded
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * used_material_return
     *
     * @return void
     */
    public function used_material_return()
    {
        return $this->belongsTo(UsedMaterialReturn::class);
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
