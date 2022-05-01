<?php

namespace App\Http\Controllers;

use App\Pack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PackController extends Controller
{
    public function index()
    {
        return Pack::all();
    }

    public function setDataCuotas() {
        // $sql = "update cuotas set IMP_PACK = (select round(packs.`premio`/packs.`cuotas`,2) premio
        // from list_opers where list_opers.Referencia = cuotas.POLIZA and list_opers.`Supl/End`=0 and list_opers.secc=99 limit 1)";

        $sql = "update cuotas set imp_pack = (
            select round(imp_pack/cuotas,2)
            from packs
            where
                cuotas.poliza REGEXP '^[0-9]+$'
            and trim(cuotas.poliza) <> ''
            and length(cuotas.poliza) = 8
            and packs.poliza = cuotas.POLIZA
            limit 1
        )";

        DB::select($sql);

        $sql = "update cuotas set nropersona = (
            select nropersona
            from packs
            where
                cuotas.poliza REGEXP '^[0-9]+$'
            and trim(cuotas.poliza) <> ''
            and length(cuotas.poliza) = 8
            and packs.poliza = cuotas.POLIZA
            limit 1
        )";

        DB::select($sql);

        return ['ok'=>true];
    }

}
