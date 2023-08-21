<?php

namespace App\Models;

class MaterialDiscrepancy extends BaseModel
{
    /**
        * appends
        *
        * @var array
        */
    protected $appends = [
        'need_approval',
        'created_by_me',
        'last_approval'
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
     * getIsNeedApprovalAttribute
     *
     * @return void
     */
    public function getLastApprovalAttribute()
    {
        $apprs = $this->approvals()
            ->select(['type_id', 'job_position_id', 'status', 'status_name'])
            ->where('type_id', $this->id)
            ->get();
        return collect($apprs)->last() ?? null;
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
     * details
     *
     * @return mixed
     */
    public function details()
    {
        return $this->hasMany(MaterialDiscrepancyDetail::class, 'material_discrepancy_id', 'id');
    }

    /**
     * approvals
     *
     * @return mixed
     */
    public function approvals()
    {
        return $this->hasMany(Approval::class, 'type_id', 'id')
            ->where('type', 'material-discrepancy')
            ->orderBy('created_at', 'ASC');
    }
}
