<?php

namespace App\Models;


class SubCategory extends BaseModel
{
    /**
     * category
     *
     * @return void
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
