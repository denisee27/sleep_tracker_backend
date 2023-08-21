<?php

namespace App\Models;

class MaterialToSiteDetailPhoto extends BaseModel
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
    public function material_to_site_detail()
    {
        return $this->belongsTo(MaterialToSiteDetail::class);
    }
}
