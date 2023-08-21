<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Services\TroubleTicketService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class TroubleTicketController extends Controller
{
    /**
     * index
     *
     * @return void
     */
    public function index()
    {
        $db_tt = DB::table('material_to_sites')
            ->selectRaw('ticket_number')
            ->where('status', 1)
            ->get()
            ->pluck('ticket_number')->all();
        $items = collect((new TroubleTicketService())->get())->filter(function ($i) use ($db_tt) {
            return !in_array($i->ticket_no, $db_tt);
        })->values()->all();
        $data['data'] = $items;
        $r = ['status' => Response::HTTP_OK, 'result' => $data];
        return response()->json($r, Response::HTTP_OK);
    }
}
