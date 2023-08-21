<?php

namespace App\Models;

class ApprovalSettingRule extends BaseModel
{
    /**
     * guarded
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * approval_setting
     *
     * @return void
     */
    public function approval_setting()
    {
        return $this->belongsTo(ApprovalSetting::class, 'approval_setting_id');
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
}
