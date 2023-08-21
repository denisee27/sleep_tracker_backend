<?php

namespace App\Models;

class JobPositionWarehouse extends BaseModel
{
    /**
     * guarded
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * job_position
     *
     * @return void
     */
    public function job_position()
    {
        return $this->belongsTo(JobPosition::class);
    }

    /**
     * warehouse
     *
     * @return void
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
