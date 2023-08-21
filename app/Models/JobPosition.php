<?php

namespace App\Models;

class JobPosition extends BaseModel
{
    /**
     * parent
     *
     * @return void
     */
    public function parent()
    {
        return $this->belongsTo(__CLASS__, 'job_position_id', 'id');
    }

    /**
     * childs
     *
     * @return void
     */
    public function childs()
    {
        return $this->hasMany(__CLASS__, 'job_position_id', 'id');
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

    /**
     * role
     *
     * @return void
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * users
     *
     * @return void
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * warehouses
     *
     * @return void
     */
    public function warehouses()
    {
        return $this->hasMany(JobPositionWarehouse::class);
    }
}
