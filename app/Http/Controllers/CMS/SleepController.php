<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\SleepHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class SleepController extends Controller
{
    /**
     * create
     *
     * @param  mixed $request
     * @return void
     */
    public function create(Request $request)
    {
        $data = json_decode($request->data, true);
        $validator = Validator::make($data, [
            'sleep_start' => 'required|date_format:Y-m-d H:i',
            'sleep_end' => 'required|date_format:Y-m-d H:i',
            'sleep_quality' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();
        $item = new SleepHistory();
        $item->user_id = auth()->user()->id;
        $sleepStart = Carbon::parse($data->sleep_start);
        $sleepEnd = Carbon::parse($data->sleep_end);
        $item->sleep_start = $sleepStart;
        $item->sleep_end = $sleepEnd;
        $minuteDuration = $sleepEnd->diffInMinutes($sleepStart);
        $convertDuration = $minuteDuration / 60;
        $convertDuration = round($convertDuration, 1);
        $item->sleep_duration = $convertDuration;
        $item->sleep_quality = $data->sleep_quality;
        $item->save();
        $r = ['status' => Response::HTTP_OK, 'result' => 'ok'];
        return response()->json($r, Response::HTTP_OK);
    }
    /**
     * create
     *
     * @param  mixed $request
     * @return void
     */
    public function daily (Request $request)
    {
        $data = [];
        $items = SleepHistory::query();
        $items->where('user_id',auth()->user()->id);
        $items->where('created_at','<=',Carbon::today()->addDays(14));
        $items->orderBy('created_at', 'DESC');    
        $data['data'] = $items->get();
        $r = ['status' => Response::HTTP_OK, 'result' => $data];
        return response()->json($r, Response::HTTP_OK);
    }

    
    /**
     * week
     *
     * @param  mixed $request
     * @param  mixed $id
     * @return void
     */
    public function week(Request $request)
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
        $averageWeek = $query->avg(DB::raw('UNIX_TIMESTAMP(sleep_end)'));
        $result = date('H:i:s', $averageWeek);
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
        $items[] = [
            'date' => $history->created_at,
            'duration' => $history->sleep_duration,
        ];
    }
    return $items;
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
            'time' => date('h:i', strtotime($history->sleep_start)),
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
