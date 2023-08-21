<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    /**
     * index
     *
     * @return void
     */
    public function index()
    {
        // $items = (new NotificationService(auth()->user()))->getAll();
        // $items->orderBy('created_at', 'desc');
        // $data = $items->paginate(10);
        $data = [];
        $r = ['status' => Response::HTTP_OK, 'result' => $data];
        return response()->json($r, Response::HTTP_OK);
    }

    /**
     * count
     *
     * @return void
     */
    public function count()
    {
        // $items = (new NotificationService(auth()->user()))->getCount();
        $items = 0;
        $r = ['status' => Response::HTTP_OK, 'result' => $items];
        return response()->json($r, Response::HTTP_OK);
    }

    /**
     * read
     *
     * @param  mixed $request
     * @return void
     */
    public function read(Request $request)
    {
        (new NotificationService(auth()->user()))->read($request->id);
        $r = ['status' => Response::HTTP_OK, 'result' => 'ok'];
        return response()->json($r, Response::HTTP_OK);
    }

    /**
     * delete
     *
     * @return void
     */
    public function delete()
    {
        (new NotificationService(auth()->user()))->delete();
        $r = ['status' => Response::HTTP_OK, 'result' => 'ok'];
        return response()->json($r, Response::HTTP_OK);
    }
}
