<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\SleepHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class SleepMonthController extends Controller
{
    /**
     * month
     *
     * @param  mixed $request
     * @param  mixed $id
     * @return void
     */
    public function month(Request $request)
    {
        $data['average_duration'] = $this->get_average_duration();
        $data['total_duration'] = $this->get_total_duration();
        $data['average_sleep'] = $this->get_average_sleep();
        $data['average_wake'] = $this->get_average_wake();
        
        $data['chart_duration'] = $this->get_chart_duration();
        $data['line_Wake'] = $this->get_line_wake();
        $data['line_sleep'] = $this->get_line_sleep();
        $r = ['status' => Response::HTTP_OK, 'result' => $data];
        return response()->json($r, Response::HTTP_OK);
    }

     /**
     * get_average_duration
     *
     * @return void
     */
    private function get_average_duration()
    {
        $query = SleepHistory::query();
        $query->where('user_id',auth()->user()->id);
        $items = $query->get();
        return ['result' => $items->average('sleep_duration') ?? 0.0];
    }
     /**
     * get_total_duration
     *
     * @return void
     */
    private function get_total_duration()
    {
        $query = SleepHistory::query();
        $query->where('user_id',auth()->user()->id);
        $items = $query->get();
        return ['result' => $items->sum('sleep_duration') ?? 0.0];
    }

   
    /**
     * get_average_sleep
     *
     * @return array
     */
    private function get_average_sleep()
    {
        $query = SleepHistory::query();
        $query->where('user_id',auth()->user()->id);
        $averageSleep = $query->avg(DB::raw('UNIX_TIMESTAMP(sleep_start)'));
        $result = date('H:i:s', $averageSleep);
        return ['average_time' => $result];
    }

    /**
     * get_average_wake
     *
     * @return array
     */
    private function get_average_wake()
    {
        $query = SleepHistory::query();
        $query->where('user_id',auth()->user()->id);
        $averagemonth = $query->avg(DB::raw('UNIX_TIMESTAMP(sleep_end)'));
        $result = date('H:i:s', $averagemonth);
        return ['average_time' => $result];
    }

        /**
     * getSleep
     *
     * @return mixed
     */
    private function getSleep()
    {
        return SleepHistory::where('user_id',auth()->user()->id)
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    /**
     * get_chart_Duration
     *
     * @return array
     */
    private function get_chart_Duration()
    {
        $items = [];

        foreach ($this->getSleep() as $history) {
            $date = $history->created_at->toDateString(); // Ambil hanya tanggal dari timestamp
            $duration = $history->sleep_duration;
    
            // Jika tanggal sudah ada, tambahkan durasi
            if (array_key_exists($date, $items)) {
                $items[$date]['duration'] += $duration;
            } else {
                // Jika tanggal belum ada, tambahkan data baru
                $items[$date] = [
                    'date' => $date,
                    'duration' => $duration,
                ];
            }
        }
    
        return array_values($items); // Ubah indeks array ke numeric
    }

    /**
     * get_line_wake
     *
     * @return array
     */
    private function get_line_wake()
    {
    $items = [];
    foreach ($this->getSleep() as $history) {
        $items[] = [
            'wake' => $history->sleep_start,
            'time' => date('H:i:s', strtotime($history->sleep_start)),
        ];
    }
    return $items;
    }

    /**
     * get_line_sleep
     *
     * @return array
     */
    private function get_line_sleep()
    {
    $items = [];
    foreach ($this->getSleep() as $history) {
        $items[] = [
            'sleep' => $history->sleep_end,
            'time' => date('h:i', strtotime($history->sleep_end)),
        ];
    }
    return $items;
    }
}
