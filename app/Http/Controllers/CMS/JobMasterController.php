<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\JobMaster;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
class JobMasterController extends Controller
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
        $items = JobMaster::query();
        $items->orderBy('name', 'asc');
        $data['data'] = $items->get();
        $r = ['status' => Response::HTTP_OK, 'result' => $data];
        return response()->json($r, Response::HTTP_OK);
    }

}
