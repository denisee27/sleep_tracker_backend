<?php

namespace App\Models;

class Warehouse extends BaseModel
{
    /**
     * appends
     *
     * @var array
     */
    protected $appends = [
        'in_stock_opname',
        'in_operation'
    ];

    /**
     * getInStockOpnameAttribute
     *
     * @return void
     */
    public function getInStockOpnameAttribute()
    {
        return $this->stock_opnames()
            ->where('status', 0)
            ->count() > 0;
    }

    /**
     * getInOperationAttribute
     *
     * @return void
     */
    public function getInOperationAttribute()
    {
        $tf_in =  $this->transfers_in()
            ->where('status', 0)
            ->count() > 0;
        $tf_out =  $this->transfers_out()
            ->where('status', 0)
            ->count() > 0;
        $mts =  $this->material_to_sites()
            ->where('status', 0)
            ->count() > 0;
        $sp_inb =  $this->supplier_inbounds()
            ->where('status', 0)
            ->count() > 0;
        return $tf_in || $tf_out || $mts || $mts || $sp_inb;
    }

    /**
     * transfers_in
     *
     * @return mixed
     */
    public function transfers_in()
    {
        return $this->hasMany(Transfer::class, 'from_warehouse', 'id');
    }

    /**
     * transfers_out
     *
     * @return mixed
     */
    public function transfers_out()
    {
        return $this->hasMany(Transfer::class, 'to_warehouse', 'id');
    }

    /**
     * material_to_sites
     *
     * @return mixed
     */
    public function material_to_sites()
    {
        return $this->hasMany(MaterialToSite::class, 'from_warehouse', 'id');
    }

    /**
     * supplier_inbound
     *
     * @return mixed
     */
    public function supplier_inbounds()
    {
        return $this->hasMany(SupplierInbound::class);
    }

    /**
     * stock_opnames
     *
     * @return mixed
     */
    public function stock_opnames()
    {
        return $this->hasMany(StockOpname::class);
    }

    /**
     * stocks
     *
     * @return void
     */
    public function stocks()
    {
        return $this->hasMany(MaterialStock::class);
    }
}
