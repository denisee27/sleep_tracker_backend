<?php

namespace App\Models;

class MaterialStock extends BaseModel
{
    /**
     * guarded
     *
     * @var array
     */
    protected $guarded = [];

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
     * warehouse
     *
     * @return void
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * projoect
     *
     * @return void
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * details
     *
     * @return void
     */
    public function details()
    {
        return $this->hasMany(MaterialStockDetail::class);
    }
}
