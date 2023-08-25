<?php

namespace App\Models;


class RequestAsset extends BaseModel
{
    /**
     * company
     *
     * @return void
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * details
     *
     * @return void
     */
    public function details()
    {
        return $this->hasMany(RequestAssetDetail::class);
    }
}
