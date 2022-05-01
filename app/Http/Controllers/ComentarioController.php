<?php

namespace App\Http\Controllers;

use App\Comentario;
use Illuminate\Http\Request;

class ComentarioController extends Controller
{

    public function index()
    {
        //
    }

    public function save(Request $request) {
        if($request->POLIZA) {
            $comentario = Comentario::firstOrNew([
                'POLIZA' => $request->POLIZA
            ]);
            $comentario->tipo = $request->tipo_comentario;
            $comentario->comentario = substr($request->comentario, 0, 499);
            $comentario->save();
            return ['ok'=> true];
        }
    }

}
