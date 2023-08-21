<?php

namespace App\Models;

class SupplierInbound extends BaseModel
{
    /**
     * appends
     *
     * @var array
     */
    protected $appends = [
        'need_approval',
        'created_by_me',
        'last_approval',
        'is_partial',
        'is_partial_complete',
        'partial_approvals'
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
            ->where(function ($q) {
                $q->where('job_position_id', auth()->user()->job_position_id)
                    ->orWhereRaw("JSON_CONTAINS(`another_job_positions`, '\"" . auth()->user()->job_position_id . "\"')");
            })
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
     * getIsPartialAttribute
     *
     * @return void
     */
    public function getIsPartialAttribute()
    {
        return $this->childs()->count() > 0;
    }

    /**
     * getIsPartialCompleteAttribute
     *
     * @return void
     */
    public function getIsPartialCompleteAttribute()
    {
        if (!$this->getIsPartialAttribute()) {
            return null;
        }
        $details = collect($this->details()->get());
        $req_qty = $details->sum('qty');
        $rec_qty = $details->sum(function ($i) {
            return ((float)$i->good_qty) + ((float)$i->bad_qty) + ((float)$i->lost_qty);
        });
        $parentQty = $req_qty - $rec_qty;
        $child_details = collect($this->childs()->where('status', 1)->with('details')->get())->sum(function ($i) {
            $d = $i->details->sum(function ($e) {
                return ((float)$e->good_qty) + ((float)$e->bad_qty) + ((float)$e->lost_qty);
            });
            return $d;
        });
        return $child_details == $parentQty;
    }

    /**
     * getPartialApprovalAttribute
     *
     * @return void
     */
    public function getPartialApprovalsAttribute()
    {
        if (!$this->getIsPartialAttribute()) {
            return null;
        }
        $childs = $this->childs()->where('status', 0)->with([
            'approvals' => function ($q) {
                $q->select(['job_position_id', 'type', 'type_id', 'status', 'status_name', 'status_order'])
                    ->where('status', 0)
                    ->where('show_notification', 1)
                    ->with(['job_position:id,role_id', 'job_position.role:id,name']);
            }
        ])->get();
        return collect($childs)->map(function ($i) {
            return $i->approvals;
        })->first();
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
     * purchase_order
     *
     * @return void
     */
    public function purchase_order()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * warehouse
     *
     * @return void
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * details
     *
     * @return mixed
     */
    public function details()
    {
        return $this->hasMany(SupplierInboundDetail::class, 'supplier_inbound_id', 'id');
    }

    /**
     * approvals
     *
     * @return mixed
     */
    public function approvals()
    {
        return $this->hasMany(Approval::class, 'type_id', 'id')
            ->where('type', 'supplier-inbound')
            ->orderBy('created_at', 'ASC');
    }

    /**
     * childs
     *
     * @return mixed
     */
    public function childs()
    {
        return $this->hasMany(__CLASS__, 'supplier_inbound_id', 'id');
    }

    /**
     * parent
     *
     * @return mixed
     */
    public function parent()
    {
        return $this->belongsTo(__CLASS__, 'supplier_inbound_id', 'id');
    }
}
