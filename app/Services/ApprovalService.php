<?php

namespace App\Services;

use App\Jobs\ApprovalMailJob;
use App\Models\Approval;
use App\Models\ApprovalSetting;
use App\Models\JobPosition;
use Illuminate\Support\Facades\DB;

class ApprovalService
{
    /**
     * item
     *
     * @var mixed
     */
    /**
     * item
     *
     * @var mixed
     */
    protected $item;

    /**
     * approvalName
     *
     * @var mixed
     */
    protected $approvalName;

    /**
     * origin
     *
     * @var mixed
     */
    protected $origin;

    /**
     * destination
     *
     * @var mixed
     */
    protected $destination;

    /**
     * useExceptions
     *
     * @var mixed
     */
    protected $useExceptions;

    protected $wh;

    /**
     * __construct
     *
     * @param  mixed $dataItem
     * @param  mixed $approvalName
     * @param  mixed $origin
     * @param  mixed $destination
     * @return void
     */
    public function __construct($dataItem, $approvalName, $origin = null, $destination = null, $useExceptions = false)
    {
        $this->item = $dataItem;
        $this->approvalName = $approvalName;
        $this->origin = $origin;
        $this->destination = $destination;
        $this->useExceptions = $useExceptions;
    }

    /**
     * createApproval
     *
     * @return void
     */
    public function createApproval()
    {
        $approvals = ApprovalSetting::where('name', $this->approvalName)
            ->where('status', 1)
            ->when($this->origin, function ($q) {
                $q->where('origin', $this->origin);
            })
            ->when($this->destination, function ($q) {
                $q->where('destination', $this->destination);
            })
            ->with([
                'rules' => function ($q) {
                    $q->orderBy('level_order', 'ASC');
                },
                'exceptions'
            ])
            ->get();
        if (!count($approvals)) {
            DB::rollBack();
            abort(422, 'No approval settings found!');
        }

        if (!$this->useExceptions) {
            $approval = $approvals[0];
        } else {
            $_exData = [];
            foreach ($this->item->details()->get() as $i) {
                $_exData[] = ['item' => $i->material->category_id, 'qty' => $i->qty];
            }

            $approval = $approvals->filter(function ($i) use ($_exData) {
                return $i->exceptions->filter(function ($e) use ($_exData) {
                    foreach ($_exData as $_exD) {
                        return eval("return '" . $e->category_id . "' == '" . $_exD['item'] . "' && " . $_exD['qty'] . " " . $e->type . " " .  $e->qty . ";");
                    }
                })->count() > 0;
            })->first();

            if (!$approval) {
                $approval = $approvals[0];
            }
        }

        foreach ($approval->rules as $i => $rule) {
            $jobPosition = JobPosition::where('role_id', $rule->role_id)
                ->with(['users', 'warehouses'])
                ->whereHas('users', function ($q) {
                    $q->where('status', 1);
                })
                ->get();

            if (count($jobPosition) > 1) {
                $selectedJobPosition = $jobPosition->filter(function ($r) {
                    if (!auth()->user()->job_position->job_position_id) {
                        return true;
                    }
                    return $r->id == auth()->user()->job_position->job_position_id;
                });
                if ($selectedJobPosition->count() == 1) {
                    $selectedJobPosition = $selectedJobPosition->first();
                } else {
                    if ($this->approvalName == 'material-to-site' && $rule->level_name == 'reception') {
                        $selectedJobPosition = auth()->user()->job_position;
                    } else {
                        $selectedJobPosition = $jobPosition->filter(function ($r) {
                            if ($this->approvalName != 'transfer' && $this->approvalName != 'material-to-site' && $this->approvalName != 'supplier-inbound') {
                                return true;
                            }
                            if ($this->approvalName == 'transfer') {
                                $keyName = 'to_warehouse';
                            } elseif ($this->approvalName == 'material-to-site') {
                                $keyName = 'from_warehouse';
                            } elseif ($this->approvalName == 'supplier-inbound') {
                                $keyName = 'warehouse_id';
                            }
                            return in_array($this->item->{$keyName}, collect($r->warehouses)->pluck('warehouse_id')->toArray());
                        })->values();
                        $selectedJobPosition = $selectedJobPosition->first() ?? null;
                        if (!$selectedJobPosition) {
                            $selectedJobPosition = (object) [
                                'id' => $jobPosition[0]->id,
                                'users' => [],
                                'job_positions' => []
                            ];
                            foreach ($jobPosition as $item) {
                                foreach ($item->users as $u) {
                                    $selectedJobPosition->users[] = (object)$u;
                                    $selectedJobPosition->job_positions[] = $u->job_position_id;
                                }
                            }
                        }
                    }
                }
            } else {
                $selectedJobPosition = $jobPosition->first() ?? null;
            }
            if (!$selectedJobPosition) {
                continue;
            }
            $approval = new Approval();
            $approval->job_position_id = $selectedJobPosition->id;
            $approval->another_job_positions = $selectedJobPosition->job_positions ?? null;
            $approval->type = $this->approvalName;
            $approval->type_id = $this->item->id;
            $approval->status = 0;
            $approval->status_name = $rule->level_name;
            $approval->status_order = $i;
            $approval->show_notification = ($i == 0) ? 1 : 0;
            $approval->save();

            if ($approval->show_notification == 1) {
                foreach (($selectedJobPosition->users ?? []) as $user) {
                    $url_name = $this->approvalName == 'transfer' ? 'transfer-material' : $this->approvalName;
                    $url = '/' . $url_name . '/detail/' . $this->item->id;
                    $name = ucwords(str_replace('-', ' ', $url_name));
                    $type = $this->approvalName == 'material-to-site' ? ($rule->level_name == 'reception' ? 'Confirmation' : 'Approval') : ucwords($rule->level_name);
                    $title = '1 ' . $name . ' is waiting your ' . $type;
                    $desc = 'Hi ' . $user->name . ' you have 1 ' . $name . ' transaction awaiting your ' . $type . ' with ID ' . $this->item->number;
                    (new NotificationService($user))->create($title, $desc, $url);
                    if ($user->wa_number) {
                        (new WhatsAppService())->sendNotification($user->wa_number, $user->name, $name, $this->item->number, $type);
                    }
                    if ($user->email) {
                        dispatch(new ApprovalMailJob($user->email, [
                            'name' => $user->name,
                            'transaction_name' => $name,
                            'transaction_id' => $this->item->number,
                            'approval_type' => $type,
                            'url' => $url,
                            'subject' => 'IMS - ' . $name . ' ' . $type
                        ]));
                    }
                }
            }
        }
    }

    /**
     * approve
     *
     * @return void
     */
    public function approve($skipNotif = false)
    {
        $approval = Approval::where('type', $this->approvalName)
            ->where('type_id', $this->item->id)
            ->where('status', 0)
            ->where(function ($q) {
                $q->where('job_position_id', auth()->user()->job_position_id)
                    ->orWhereRaw("JSON_CONTAINS(`another_job_positions`, '\"" . auth()->user()->job_position_id . "\"')");
            })
            ->orderBy('status_order', 'ASC')
            ->firstOrFail();
        $approval->status = 1;
        $approval->updated_by = auth()->user()->id;
        $approval->save();

        $nextApproval = Approval::where('type', $this->approvalName)
            ->where('type_id', $this->item->id)
            ->where('status', 0)
            ->where('show_notification', 0)
            ->orderBy('status_order', 'ASC')
            ->first();
        if (!$nextApproval) {
            $this->item->status = 1;
            $this->item->save();
        } else {
            $jobPosition = JobPosition::where('role_id', $nextApproval->job_position->role_id)
                ->with(['users', 'warehouses'])
                ->whereHas('users', function ($q) {
                    $q->where('status', 1);
                })
                ->get();
            if (count($jobPosition) > 1) {
                $selectedJobPosition = $jobPosition->filter(function ($r) {
                    if (!auth()->user()->job_position->job_position_id) {
                        return true;
                    }
                    return $r->id == auth()->user()->job_position->job_position_id;
                });
                if ($selectedJobPosition->count() == 1) {
                    $selectedJobPosition = $selectedJobPosition->first();
                } else {
                    if ($this->approvalName == 'material-to-site' && $nextApproval->status_name == 'reception') {
                        $_usr = $this->item->creator->load(['job_position', 'job_position.users']);
                        $selectedJobPosition = $_usr->job_position;
                    } else {
                        $selectedJobPosition = $jobPosition->filter(function ($r) {
                            if ($this->approvalName != 'transfer' && $this->approvalName != 'material-to-site' && $this->approvalName != 'supplier-inbound') {
                                return true;
                            }
                            if ($this->approvalName == 'transfer') {
                                $keyName = 'to_warehouse';
                            } elseif ($this->approvalName == 'material-to-site') {
                                $keyName = 'from_warehouse';
                            } elseif ($this->approvalName == 'supplier-inbound') {
                                $keyName = 'warehouse_id';
                            }
                            return in_array($this->item->{$keyName}, collect($r->warehouses)->pluck('warehouse_id')->toArray());
                        })->values();
                        $selectedJobPosition = $selectedJobPosition->first() ?? null;
                        if (!$selectedJobPosition) {
                            $selectedJobPosition = (object) [
                                'id' => $jobPosition[0]->id,
                                'users' => [],
                                'job_positions' => []
                            ];
                            foreach ($jobPosition as $item) {
                                foreach ($item->users as $u) {
                                    $selectedJobPosition->users[] = (object)$u;
                                    $selectedJobPosition->job_positions[] = $u->job_position_id;
                                }
                            }
                        }
                    }
                }
            } else {
                $selectedJobPosition = $jobPosition->first() ?? null;
            }
            if ($selectedJobPosition) {
                $nextApproval->job_position_id = $selectedJobPosition->id;
            }
            $nextApproval->show_notification = 1;
            $nextApproval->another_job_positions = $selectedJobPosition->job_positions ?? null;
            $nextApproval->save();
            if ($skipNotif) {
                return;
            }
            if ($nextApproval->show_notification == 1) {
                foreach (($selectedJobPosition->users ?? []) as $user) {
                    $url_name = $this->approvalName == 'transfer' ? 'transfer-material' : $this->approvalName;
                    $url = '/' . $url_name . '/detail/' . $this->item->id;
                    $name = ucwords(str_replace('-', ' ', $url_name));
                    $type = $this->approvalName == 'material-to-site' ? ($nextApproval->status_name == 'reception' ? 'Confirmation' : 'Approval') : ucwords($nextApproval->status_name);
                    $title = '1 ' . $name . ' is waiting your ' . $type;
                    $desc = 'Hi ' . $user->name . ' you have 1 ' . $name . ' transaction awaiting your ' . $type . ' with ID ' . $this->item->number;
                    (new NotificationService($user))->create($title, $desc, $url);
                    if ($user->wa_number) {
                        (new WhatsAppService())->sendNotification($user->wa_number, $user->name, $name, $this->item->number, $type);
                    }
                    if ($user->email) {
                        dispatch(new ApprovalMailJob($user->email, [
                            'name' => $user->name,
                            'transaction_name' => $name,
                            'transaction_id' => $this->item->number,
                            'approval_type' => $type,
                            'url' => $url,
                            'subject' => 'IMS - ' . $name . ' ' . $type
                        ]));
                    }
                }
            }
        }
    }

    /**
     * reject
     *
     * @return void
     */
    public function reject($remarks)
    {
        $approval = Approval::where('type', $this->approvalName)
            ->where('type_id', $this->item->id)
            ->where('status', 0)
            ->where(function ($q) {
                $q->where('job_position_id', auth()->user()->job_position_id)
                    ->orWhereRaw("JSON_CONTAINS(`another_job_positions`, '\"" . auth()->user()->job_position_id . "\"')");
            })
            ->orderBy('status_order', 'ASC')
            ->firstOrFail();
        $approval->status = -1;
        $approval->remarks = $remarks;
        $approval->updated_by = auth()->user()->id;
        $approval->save();

        Approval::where('type', $this->approvalName)
            ->where('type_id', $this->item->id)
            ->where('status', 0)
            ->forceDelete();

        $this->item->status = -1;
        $this->item->save();
    }

    /**
     * receive
     *
     * @return void
     */
    public function receive()
    {
        $approval = Approval::where('type', $this->approvalName)
            ->where('type_id', $this->item->id)
            ->where('status', 0)
            ->where(function ($q) {
                $q->where('job_position_id', auth()->user()->job_position_id)
                    ->orWhereRaw("JSON_CONTAINS(`another_job_positions`, '\"" . auth()->user()->job_position_id . "\"')");
            })
            ->orderBy('status_order', 'ASC')
            ->firstOrFail();
        $approval->status = 1;
        $approval->updated_by = auth()->user()->id;
        $approval->save();
    }

    /**
     * remove
     *
     * @return void
     */
    public function remove(array $selectedIds = null, $permanent = true)
    {
        $appr = Approval::where('type', $this->approvalName)->whereIn('type_id', $selectedIds ?? [$this->item->id]);
        if ($permanent) {
            $appr->delete();
        } else {
            $appr->delete();
        }
    }
}
