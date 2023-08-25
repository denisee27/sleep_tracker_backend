<?php

namespace App\Models;


class AssetHistory extends BaseModel
{
    /**
     * asset
     *
     * @return void
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * from_user
     *
     * @return void
     */
    public function from_user()
    {
        return $this->belongsTo(User::class, 'from_user_id', 'id');
    }

    /**
     * to_user
     *
     * @return void
     */
    public function to_user()
    {
        return $this->belongsTo(User::class, 'to_user_id', 'id');
    }
}
