<?php

namespace App\Models;


class RequestAssetDetail extends BaseModel
{
    /**
     * guarded
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * request_asset
     *
     * @return void
     */
    public function request_asset()
    {
        return $this->belongsTo(RequestAsset::class);
    }

    /**
     * sub_category
     *
     * @return void
     */
    public function subcategory()
    {
        return $this->belongsTo(SubCategory::class,'sub_category_id');
    }
}
