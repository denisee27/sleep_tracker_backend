<?php

namespace App\Models;

class PurchaseOrder extends BaseModel
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
     * details
     *
     * @return void
     */
    public function details()
    {
        return $this->hasMany(PurchaseOrderDetail::class);
    }

    /**
     * supplier
     *
     * @return void
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * company
     *
     * @return void
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
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
     * approvals
     *
     * @return mixed
     */
    public function approvals()
    {
        return $this->hasMany(Approval::class, 'type_id', 'id')
            ->where('type', 'purchase-orders')
            ->orderBy('created_at', 'ASC');
    }

    /**
     * supplier_inbound
     *
     * @return void
     */
    public function supplier_inbound()
    {
        return $this->hasOne(SupplierInbound::class, 'purchase_order_id');
    }
}
