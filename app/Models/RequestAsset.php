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
     * company
     *
     * @return void
     */
    public function creator()
    {
        return $this->belongsTo(User::class,'created_by');
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
