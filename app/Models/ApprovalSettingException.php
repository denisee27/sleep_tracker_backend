<?php

namespace App\Models;

class ApprovalSettingException extends BaseModel
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
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
