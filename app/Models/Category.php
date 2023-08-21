<?php

namespace App\Models;

class Category extends BaseModel
{
    /**
     * materials
     *
     * @return void
     */
    public function materials()
    {
        return $this->hasMany(Material::class, 'category_id', 'id');
    }
}
