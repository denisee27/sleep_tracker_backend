<?php

namespace App\Models;


class AssetController extends BaseModel
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
