<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\SleepHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class SleepDailyController extends Controller
{
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

}
