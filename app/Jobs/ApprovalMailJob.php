<?php

namespace App\Jobs;

use App\Mail\ApprovalMail;
use Illuminate\Support\Facades\Mail;

class ApprovalMailJob extends Job
{
    /**
     * to
     *
     * @var mixed
     */
    protected $to;


    /**
     * data
     *
     * @var mixed
     */
    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($to, $data)
    {
        $this->to = $to;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $mail = new ApprovalMail($this->data);
            Mail::to($this->to)->send($mail);
        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
