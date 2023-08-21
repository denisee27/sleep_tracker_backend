<?php

namespace App\Models;

class UsedMaterialReturn extends BaseModel
{
    /**
      * appends
      *
      * @var array
      */
    protected $appends = [
        'need_approval',
        'created_by_me',
    ];

    /**
     * getIsNeedApprovalAttribute
     *
     * @return void
     */
    public function getNeedApprovalAttribute()
    {
        return $this->approvals()
            ->where('type_id', $this->id)
            ->where('status', 0)
            ->where('show_notification', 1)
            ->where('job_position_id', auth()->user()->job_position_id)
            ->count() > 0;
    }

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
     * material_to_site
     *
     * @return void
     */
    public function material_to_site()
    {
        return $this->belongsTo(MaterialToSite::class);
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
        return $this->hasMany(UsedMaterialReturnDetail::class);
    }

    /**
     * approvals
     *
     * @return mixed
     */
    public function approvals()
    {
        return $this->hasMany(Approval::class, 'type_id', 'id')
            ->where('type', 'used-material-return')
            ->orderBy('created_at', 'ASC');
    }
}
