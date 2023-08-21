<?php

namespace App\Models;

class StockAlertNotification extends BaseModel
{
    /**
     * warehouses
     *
     * @return void
     */
    public function warehouses()
    {
        return $this->hasMany(StockAlertNotificationWarehouse::class);
    }

    /**
     * users
     *
     * @return void
     */
    public function users()
    {
        return $this->hasMany(StockAlertNotificationUser::class);
    }
}
