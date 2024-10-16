<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;

use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;


class TypeController extends Controller
{
    public function create(Request $request)
    {
        $data = json_decode($request->data, true);
        $validator = Validator::make($data, [
            'name' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'wrong' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = (object) $validator->validated();
        $item = new Type();
        $item->name = $data->name;
        $item->save();
        $r = ['status' => Response::HTTP_OK, 'result' => 'ok'];
        return response()->json($r, Response::HTTP_OK);
    }
}
