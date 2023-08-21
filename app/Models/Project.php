<?php

namespace App\Models;

class Project extends BaseModel
{
    /**
     * company
     *
     * @return void
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
