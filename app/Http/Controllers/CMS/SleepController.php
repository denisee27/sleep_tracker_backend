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
     * index
     *
     * @param  mixed $request
     * @param  mixed $id
     * @return void
     */
    public function index(Request $request, $id = null)
    {
        $data = [];
        $items = SleepHistory::query();
        $items->orderBy('nik', 'asc');
        $items->with([
            'role:id,name',
            'superior:id,name'
        ]);

        if (isset($request->filter) && $request->filter) {
            $filter = json_decode($request->filter, true);
            $items->where($filter);
        }

        if(isset($request->self) && $request->self){
            $items->whereNot(function ($q) use ($request){
                $q->where('nik','super-admin')
                ->orWhere('id',auth()->user()->id);
            });
        }

        if ($id == null) {
            if (isset($request->q) && $request->q) {
                $q = $request->q;
                $items->where(function ($query) use ($q) {
                    $query->orWhere('name', 'like', '%' . $q . '%')
                        ->orWhere('email', 'like', '%' . $q . '%')
                        ->orWhere('nik', 'like', '%' . $q . '%')
                        ->orWhere('phone', 'like', '%' . $q . '%')
                        ->orWhereHas('role', function ($query) use ($q) {
                            $query->where('name', 'like', '%' . $q . '%');
                        });
                });
            }
            if (isset($request->limit) && ((int) $request->limit) > 0) {
                $data = $items->paginate(((int) $request->limit))->toArray();
            } else {
                $data['data'] = $items->get();
                $data['total'] = count($data['data']);
            }
        } else {
            $data['data'] = $items->where('id', $id)->first();
            $data['total'] = 1;
        }
        $r = ['status' => Response::HTTP_OK, 'result' => $data];
        return response()->json($r, Response::HTTP_OK);
    }

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
        $data['total']['average_duration'] = $this->get_average_duration();
        $data['total']['total_duration'] = $this->get_total_duration();
        $data['total']['average_sleep'] = $this->get_average_sleep();
        $data['total']['average_wake'] = $this->get_average_wake();
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
        $averageWeek = $query->avg(DB::raw('UNIX_TIMESTAMP(sleep_end)'));
        $result = date('H:i:s', $averageWeek);
        return ['average_time' => $result];
    }

}
