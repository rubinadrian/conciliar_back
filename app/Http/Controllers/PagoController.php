<?php

namespace App\Http\Controllers;

use App\Pago;
use Illuminate\Http\Request;
use DateTime;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PagoController extends Controller
{
    private $campos_requeridos = [
        'NROPERSONA', 'ASEGURADO', 'OPERACION', 'CIA', 'ENTIDAD_ASEGURADORA', 'PROPUESTA', 'NRORECIBO',
        'PRODUCTO', 'POLIZA', 'SUPLE', 'CUOTA', 'EXPEDIENTE', 'EMISION', 'MONEDA',
        'COBRADO', 'VENCIMIENTO', 'FECCOBRO', 'HORCOBRO', 'NROLOTE', 'NROTERM', 'MOVIMIENTO', 'COMISION',
        'PRIMA', 'PREMIO'
    ];


    public function index() {
        $query = Pago::select(DB::raw('pagos.*,comentarios.comentario,comentarios.tipo as tipo_comentario'))
        ->doesntHave('grupopagos')
        ->leftJoin('comentarios', 'comentarios.POLIZA', '=', 'pagos.POLIZA')
        ->whereIn('cia',['1','2'])
        ->where('pagos.tipo','!=', 2)
        ->orderBy('feccobro');


        return $query->get();
        // ->select('pagos.*')
        // ->leftJoin('list_opers','list_opers.poliza/contrato','=','pagos.expediente')
    }

    public function uploadCSV()
    {
        $data = $this->csv_to_array($_FILES['file']['tmp_name'],';');
        $data = $this->removePagosExistentes($data);
        $count_before_insert = Pago::count();
        Pago::insert($data);
        $count_after_insert = Pago::count();
        $count_insert = $count_after_insert - $count_before_insert;
        return ['ok' => true, 'count_csv' => count($data), 'count_insert'=>$count_insert];
    }

    private function removePagosExistentes($data) {
        $nrorecibos = array_column($data, 'NRORECIBO');
        $pagos_existentes = Pago::select('NRORECIBO')->whereIn('NRORECIBO', $nrorecibos)->get();
        $pes = $pagos_existentes->toArray();
        $pes = array_column($pes, 'NRORECIBO');
        if($pagos_existentes) {
            $data = array_filter($data, function($v, $k) use (&$pes) {
                return !in_array($v['NRORECIBO'],$pes);
            }, ARRAY_FILTER_USE_BOTH);
        }
        return $data;
    }


    private function csv_to_array($filename='', $delimiter=',')
    {
        if(!file_exists($filename) || !is_readable($filename)) {
            Log::info('No se puede leer el archivo');
            return FALSE;
        }

        $header = NULL;
        $data = [];
        if (($handle = fopen($filename, 'r')) !== FALSE)
        {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE)
            {
                if(count($row) < count($this->campos_requeridos)) continue;

                if(!$header){
                    foreach($row as $k=>$v) {
                        $row[$k] = utf8_encode(trim($v));
                    }
                    $header = $row;
                }
                else {
                    // limpiar y formatea datos
                    $row = $this->clearData($row,$header);
                    // array_combine pone el primer array como claves y el segundo como valores.
                    $row = array_combine($header, $row);
                    if($row['POLIZA']=='') continue;
                    // (1)C.L.S.G. o (2)PERSONAS S.A.
                    if(!($row['CIA']=='1' || $row['CIA']=='2')) continue; 
                    if($row['NRORECIBO'] == 0) {
                        $row['NRORECIBO'] = $row['POLIZA'] . sprintf("%012d", $row['NROLOTE']) . sprintf("%03d", $row['MOVIMIENTO']);
                    }
                    $data[] = array_combine($header, $row);
                }
            }
            fclose($handle);
        }
        return $data;
    }

    private function clearData($row,$header) {

        foreach($row as $k=>$value) {
            $v = preg_replace('/^\"/', '', $value);
            $v = preg_replace('/^\'/', '', $v);
            $v = preg_replace('/\"$/', '', $v);
            $v = preg_replace('/\'$/', '', $v);
            $v = trim($v);
            $v = utf8_encode($v);

            // Si el campo es una fecha
            if(in_array($header[$k],['EMISION','FECCOBRO','VENCIMIENTO'])) {
                $date = DateTime::createFromFormat('d/m/Y', $row[$k]);
                if($date) {
                    $v = $date->format('Y-m-d');
                } else {
                    $v = null;
                }
            }

            // Si el campo es un numero
            if(in_array($header[$k],['COMISION','PRIMA','PREMIO','COBRADO'])) {
                if(!is_null($v) && !is_numeric($v)) {
                    $v = str_replace(',','.',$v);
                }
            }

            if($v=='') $v=null;

            $row[$k]=$v;
        }

        return $row;
    }

    public function setP2(Request $request) {
        $pago = Pago::find($request->id);
        if($pago) {
            $pago->tipo = 2; // Realizo pago por otros metodos y no se debita en 13017
            $pago->save();
        }
        return ['ok'=>true];
    }
}
