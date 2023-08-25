<?php

namespace App\Models;


class Asset extends BaseModel
{
    /**
     * sub_category
     *
     * @return void
     */
    public function sub_category()
    {
        return $this->belongsTo(SubCategory::class);
    }

    /**
     * user
     *
     * @return void
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }


    /**
     * histories
     *
     * @return void
     */
    public function histories()
    {
        return $this->hasMany(AssetHistory::class, 'asset_id', 'id');
    }
}
