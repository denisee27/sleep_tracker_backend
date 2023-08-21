<?php

namespace App\Models;

class PoSap extends BaseModel
{
    /**
     * project
     *
     * @return void
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    /**
     * details
     *
     * @return void
     */
    public function details()
    {
        return $this->hasMany(PoSapDetail::class);
    }

    /**
     * activator
     *
     * @return void
     */
    public function activator()
    {
        return $this->belongsTo(User::class, 'activated_by', 'id');
    }
}
