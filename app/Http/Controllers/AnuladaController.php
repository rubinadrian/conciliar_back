<?php

namespace App\Http\Controllers;

use App\Anulada;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnuladaController extends Controller
{
    public function index()
    {
        return Anulada::all();
    }

    public function setCuotasAnuladas() {
        $sql = "update cuotas set estado_poliza = 'ANULADA' where poliza in (select poliza from anuladas)
        and cuotas.poliza REGEXP '^[0-9]+$'
        and trim(poliza) <> ''
        and length(poliza) = 8";

        DB::select($sql);
        return ['ok'=>true];
    }

}
