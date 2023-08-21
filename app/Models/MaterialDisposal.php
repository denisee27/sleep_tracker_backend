<?php

namespace App\Models;

class MaterialDisposal extends BaseModel
{
    /**
     * appends
     *
     * @var array
     */
    protected $appends = [
        'created_by_me'
    ];

    /**
     * getCreatedByMeAttribute
     *
     * @return void
     */
    public function getCreatedByMeAttribute()
    {
        return $this->created_by == auth()->user()->id;
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
     * details
     *
     * @return mixed
     */
    public function details()
    {
        return $this->hasMany(MaterialDisposalDetail::class);
    }
}
