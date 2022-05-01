<?php

namespace App\Http\Controllers;

use App\Cuota;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use DateTime;
use DateInterval;

class CuotaController extends Controller
{

    public function index() {
        $date = new DateTime('now');
        $date->modify('last day of this month');
        $fecha_hasta = $date->format('Y-m-d');

        return Cuota::doesntHave('grupocuotas')
        ->select(DB::raw('cuotas.*,comentarios.comentario'))
        //->where('fechavto', '<=', $fecha_hasta)
        ->leftJoin('comentarios', 'comentarios.POLIZA', '=', 'cuotas.POLIZA')
        ->orderBy('fechavto')
        ->get();
    }

    public function cuotasConError() {
        $sql = "SELECT * FROM `cuotas` WHERE trim(poliza) = '' or length(poliza) <> 8";
        return DB::select($sql);
    }

    public function actualizarCuotas() {
        $max_nrointerno =  Cuota::max('NROINTERNO');
        if (!$max_nrointerno) $max_nrointerno = 0;
        $count = 0;
        while($cuotas = $this->get_new_cuotas($max_nrointerno)){
            $count += count($cuotas);
            $cuotas= json_decode( json_encode($cuotas), true);

            try {
                Cuota::insert($cuotas);
                $max_nrointerno =  Cuota::max('NROINTERNO');
            } catch(\Illuminate\Database\QueryException $ex){
                return response()->json(['error' => $ex->getMessage()])->setStatusCode(500);
            }
        }
        return ['ok', 'count' => $count];
    }

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
                                    AND cuenta = 13017))";

        return DB::connection('oracle')->select($sql);
    }

    private function get_new_cuotas($max_nrointerno = 0) {
        $date = new DateTime('now');
        $date->modify('first day of this month');
        $interval = new DateInterval('P4M');
        $date->sub($interval);
        $fecha_comienzo = $date->format('Y-m-d'); // desde dos meses antes.

        //REGEXP_SUBSTR(b.denominacion, '(\d{8})', 1, 1, 'i', 1) poliza,
        $sql = "SELECT produccion.get_poliza(b.denominacion) poliza,
                    DECODE (c.debcre, 2, -C.IMPORIGEN, C.IMPORIGEN) importe,
                    c.tipocomp,
                    a.nrocomprobante,
                    to_char(date_c(c.fechavto),'YYYY-mm-dd') as fechavto,
                    a.cuotas,
                    c.nrocuota,
                    a.nrointerno
                FROM
                    produccion.cabpie a,
                    produccion.cuerpo b,
                    PRODUCCION.MOVIDE c
                WHERE
                    c.tipocomp IN (316, 325, 373, 319, 320, 380)
                AND c.fechavto >= PRODUCCION.n_Date('{$fecha_comienzo}')
                AND a.nrointerno = b.nrointerno
                AND a.nrointerno > '{$max_nrointerno}'
                AND a.resford = c.resford
                AND b.articulo = '-SEGUROS'
                AND a.baja = 0
                AND b.baja = 0
                AND a.cancelacion = 3
                AND a.cuenta = 13017
                order by nrointerno
                OFFSET 0 ROWS FETCH NEXT 500 ROWS ONLY";

        return DB::connection('oracle')->select($sql);
    }

    /** Por si se borra alguna cuota este metodo actualiza desde una fecha en adelante.
     * Si no existe la cuota la inserta y si existe hace un update pero no cambia nada.
    */
    public function actualizarCuotas2() {
        $max_nrointerno = 0;
        $count = 0;
        while($cuotas = $this->get_new_cuotas($max_nrointerno)){
            $sql = '';
            $count += count($cuotas);
            try {
                if($count > 0) {
                    $cuotas= json_decode( json_encode($cuotas), true);
                    $campos = array_keys($cuotas[0]);
                    foreach($cuotas as $c) {
                        $values[] = '("' . implode('","', $c) . '")'; 
                        if($c['nrointerno'] > $max_nrointerno ) $max_nrointerno = $c['nrointerno'];
                    }
    
                    $sql = "INSERT INTO cuotas (" . implode(",", $campos) . ") values "; 
                    $sql.= implode(",", $values);
                    $sql.= ' ON DUPLICATE KEY UPDATE id=id';
                    //Log::info($sql);
                    DB::select($sql);
                }
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
