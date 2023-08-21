<?php

namespace App\Models;

class MaterialStockHistory extends BaseModel
{
    /**
     * material
     *
     * @return void
     */
    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * purchase_order
     *
     * @return void
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * project
     *
     * @return void
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * ticket_number
     *
     * @return void
     */
    public function material_to_site()
    {
        return $this->belongsTo(MaterialToSite::class, 'source_id', 'id');
    }

    /**
     * transfer
     *
     * @return void
     */
    public function transfer()
    {
        return $this->belongsTo(Transfer::class, 'source_id', 'id');
    }

    /**
     * transfer_project_code
     *
     * @return void
     */
    public function transfer_project_code()
    {
        return $this->belongsTo(TransferProjectCode::class, 'source_id', 'id');
    }
}
