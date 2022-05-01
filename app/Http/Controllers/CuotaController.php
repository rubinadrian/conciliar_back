<?php

namespace App\Http\Controllers;

use App\Cuota;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use DateTime;
use DateInterval;
use Log;

class CuotaController extends Controller
{

    public function index() {
        $date = new DateTime('now');
        $date->modify('last day of this month');
        $fecha_hasta = $date->format('Y-m-d');

        return Cuota::doesntHave('grupocuotas')
        ->select(DB::raw('cuotas.*,comentarios.comentario,comentarios.tipo as tipo_comentario'))
        //->where('fechavto', '<=', $fecha_hasta)
        ->leftJoin('comentarios', 'comentarios.POLIZA', '=', 'cuotas.POLIZA')
        ->orderBy('fechavto')
        ->get();
    }

    public function cuotasConError() {
        $sql = "SELECT * FROM `cuotas` WHERE trim(poliza) = '' or length(poliza) <> 8";
        return DB::select($sql);
    }

    /** Obtiene todas las cuotas de una poliza en la cuenta 13017 */
    public function getAllCuotasPoliza(Request $request) {
        $validatedData = $request->validate([
            'poliza' => 'required|numeric|min:99999'
        ]);

        if (!$validatedData) {
            return [];
        }

        $sql = "SELECT cuenta,
                nrocomprobante,
                debcre,
                nrocuota,
                TO_CHAR(date_c (fechaorigen), 'DD-MM-YYYY') AS fechaorigen,
                TO_CHAR(date_c (fechavto), 'DD-MM-YYYY  ') AS fechavto,
                DECODE (debcre, 2, -IMPORIGEN, IMPORIGEN) importe
        FROM movide
        WHERE resford IN
                (SELECT resford
                    FROM cabpie
                    WHERE nrointerno IN
                            (SELECT nrointerno
                                FROM cuerpo
                                WHERE     denominacion LIKE '%$request->poliza%'
                                    AND seccion = 33
                                    AND deposito = 33
                                    AND cuenta in (13017,13097)))";

        return DB::connection('oracle')->select($sql);
    }


    // public function actualizarCuotas() {
    //     $_cuotas = $this->get_new_cuotas();
    //     $count = count($_cuotas);
    //     while($cuotas = array_splice($_cuotas,0,500)){
    //         $cuotas = json_decode( json_encode($cuotas), true);

    //         try {
    //             Cuota::insert($cuotas);
    //         } catch(\Illuminate\Database\QueryException $ex){
    //             return response()->json(['error' => $ex->getMessage()])->setStatusCode(500);
    //         }
    //     }

    //     return ['ok', 'count' => $count];
    // }

    private function get_new_cuotas() {
        $date = new DateTime('now');
        $date->modify('first day of this month');
        $interval = new DateInterval('P3M'); // periodo de 3 meses
        $date->sub($interval);
        $fecha_comienzo = $date->format('Y-m-d');
        $max_nrointerno_vt = 0;
        $max_nrointerno_pc = 0;
        // $max_nrointerno_vt =  Cuota::where('SISTEMA','VT')->max('NROINTERNO');
        // if (!$max_nrointerno_vt) $max_nrointerno_vt = 0;
        // $max_nrointerno_pc =  Cuota::where('SISTEMA','PC')->max('NROINTERNO');
        // if (!$max_nrointerno_pc) $max_nrointerno_pc = 0;
        //REGEXP_SUBSTR(b.denominacion, '(\d{8})', 1, 1, 'i', 1) poliza,
            
        $sql = "SELECT * FROM
            (SELECT  produccion.get_poliza(b.denominacion) poliza,
            REGEXP_SUBSTR(b.denominacion, 'E(\d*)', 1,1, 'i', 1) expediente,
            REGEXP_SUBSTR(b.denominacion, 'S(\d*)', 1,1, 'i', 1) nropersona,
            DECODE (c.debcre, 2, -C.IMPORIGEN, C.IMPORIGEN) importe,
            c.tipocomp,
            a.cuenta,
            a.nrocomprobante,
            to_char(date_c(c.fechavto),'YYYY-mm-dd') as fechavto,
            a.cuotas,
            c.nrocuota,
            a.nrointerno,
            'VT' SISTEMA
            FROM
            produccion.cabpie a,
            produccion.cuerpo b,
            PRODUCCION.MOVIDE c
            WHERE
                c.fechavto >= PRODUCCION.n_Date('{$fecha_comienzo}')
            --AND c.tipocomp IN (316, 325, 373, 319, 320, 380)
            AND a.cuenta in (13017,13097)
            AND a.nrointerno = b.nrointerno
            AND a.nrointerno > '{$max_nrointerno_vt}'
            AND a.resford = c.resford
            and b.articulo <> '*'
            and b.preciounit > 0
            AND a.baja = 0
            AND b.baja = 0
            AND a.cancelacion = 3
            )
            UNION
            (
                SELECT 
                    TO_CHAR (nrocomprobante) poliza,
                    null as expediente,
                    null as nropersona,
                    DECODE (debcre, 2, -IMPORIGEN, IMPORIGEN) importe,
                    tipocomp,
                    cuenta,
                    nrocomprobante,
                    TO_CHAR (date_c (fechavto), 'YYYY-mm-dd') AS fechavto,
                    1 cuotas,
                    1 nrocuota,
                    resford nrointerno,
                    SISTEMA
                FROM resfac 
                WHERE
                    baja = 0
                AND resford > 0
                AND contadocc = 2
                AND actualizacion = 2
                AND sistema <> 'FA'
                AND cuenta in (13017,13097)
                AND fechavto > n_date ('{$fecha_comienzo}')
                AND resford  > '{$max_nrointerno_pc}'
            )";

            //OFFSET 0 ROWS FETCH NEXT 500 ROWS ONLY --sql de a 500 registros
            //Log::info($sql);
        return DB::connection('oracle')->select($sql);
    }

    /** Por si se borra alguna cuota este metodo actualiza desde una fecha en adelante.
     * Si no existe la cuota la inserta y si existe hace un update pero no cambia nada.
    */
    public function actualizarCuotasDesdeFecha() {
        $_cuotas = $this->get_new_cuotas();
        $count = count($_cuotas);
        while($cuotas = array_splice($_cuotas,0,500)){
            try {
                $cuotas= json_decode( json_encode($cuotas), true);
                $campos = array_keys($cuotas[0]);
                foreach($cuotas as $c) {
                    $values[] = str_replace('""','null','("' . implode('","', $c) . '")'); 
                }

                $sql = "INSERT INTO cuotas (" . implode(",", $campos) . ") values "; 
                $sql.= implode(",", $values);
                $sql.= ' ON DUPLICATE KEY UPDATE id=id';

                //Log::info($sql);
                DB::select($sql);
            } catch(\Illuminate\Database\QueryException $ex){
                return response()->json(['error' => $ex->getMessage()])->setStatusCode(500);
            }
        }
        return ['ok', 'count' => $count];
    }

}



/*
CREATE OR REPLACE FUNCTION PRODUCCION.get_poliza (denominacion VARCHAR2)
   RETURN VARCHAR2
IS
   valor   VARCHAR2(255);
BEGIN
   valor := REGEXP_SUBSTR(denominacion, '(\d{8})', 1, 1, 'i', 1);
   IF (valor is null) THEN
      return REGEXP_REPLACE(denominacion||' ','\W', ' ');
   END IF;
   RETURN (valor);
EXCEPTION
WHEN NO_DATA_FOUND THEN
  return '';
END;
/

*/
