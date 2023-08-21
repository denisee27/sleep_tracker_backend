<?php

namespace App\Jobs;

use App\Mail\GeneralMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class EmailJob extends Job
{
    protected $to;
    protected $data;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($to, $data, int $emailPerMinutes = 60)
    {
        $this->to = $to;
        $this->data = $data;
        $this->onQueue('emails');
        $lastJob = DB::table('jobs')->selectRaw('COUNT(`id`) as count')->where('queue', 'emails')->first();
        for ($i = 1; $i <= $lastJob->count ?? 1; $i++) {
            $delay = (int) floor($i / $emailPerMinutes);
            $this->delay($delay * 60);
        }
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function backoff()
    {
        return [5, 15, 30];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $mail = new GeneralMail($this->data);
            Mail::to($this->to)->send($mail);
        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
