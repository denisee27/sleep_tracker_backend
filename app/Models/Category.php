<?php

namespace App\Models;

class Category extends BaseModel
{
    /**
     * materials
     *
     * @return void
     */
    public function sub_categories()
    {
        return $this->hasMany(SubCategory::class);
    }
}
