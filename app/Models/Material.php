<?php

namespace App\Models;

class Material extends BaseModel
{
    /**
     * casts
     *
     * @var array
     */
    protected $casts = [
        'minimum_stock' => 'json',
    ];

    /**
     * category
     *
     * @return void
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * stocks
     *
     * @return void
     */
    public function stocks()
    {
        return $this->hasMany(MaterialStock::class);
    }

    /**
     * histories
     *
     * @return void
     */
    public function histories()
    {
        return $this->hasMany(MaterialStockHistory::class, 'material_id', 'id');
    }
}
