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
}
