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
    
}
