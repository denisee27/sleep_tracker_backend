<?php

namespace App\Models;

class Approval extends BaseModel
{
    /**
     * casts
     *
     * @var array
     */
    protected $casts = [
        'another_job_positions' => 'array',
    ];

    /**
     * appends
     *
     * @var array
     */
    protected $appends = [
        'is_me'
    ];

    /**
     * getIsNeedApprovalAttribute
     *
     * @return void
     */
    public function getIsMeAttribute()
    {
        return auth()->user()->job_position_id == $this->job_position_id || in_array(auth()->user()->job_position_id, ((array)$this->another_job_positions ?? []));
    }

    /**
     * job_position
     *
     * @return void
     */
    public function job_position()
    {
        return $this->belongsTo(JobPosition::class, 'job_position_id', 'id');
    }
}
