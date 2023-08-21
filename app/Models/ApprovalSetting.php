<?php

namespace App\Models;

class ApprovalSetting extends BaseModel
{
    /**
     * rules
     *
     * @return void
     */
    public function rules()
    {
        return $this->hasMany(ApprovalSettingRule::class, 'approval_setting_id')->orderBy('level_order');
    }

    /**
     * exceptions
     *
     * @return void
     */
    public function exceptions()
    {
        return $this->hasMany(ApprovalSettingException::class)->orderBy('qty', 'desc');
    }
}
