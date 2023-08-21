<?php

namespace App\Models;

class MaterialToSiteDetail extends BaseModel
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
    public function material_to_site()
    {
        return $this->belongsTo(MaterialToSite::class, 'material_to_site_id');
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
        return $this->hasMany(MaterialToSiteDetailStock::class, 'material_to_site_detail_id', 'id');
    }

    /**
     * photos
     *
     * @return void
     */
    public function photos()
    {
        return $this->hasMany(MaterialToSiteDetailPhoto::class, 'material_to_site_detail_id', 'id');
    }
}
