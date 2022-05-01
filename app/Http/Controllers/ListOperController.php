<?php

namespace App\Http\Controllers;

use App\ListOper;
use App\Pack;
use App\Anulada;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Log;

class ListOperController extends Controller
{
    private $campos_requeridos = ['Poliza/Contrato','Secc','Supl/End','CdPer','Premio','Cuotas','Estado_Poliza', 'Nro_Vinculante','Expediente'];

    public function uploadCSV()
    {
        $data = $this->csv_to_array($_FILES['file']['tmp_name'], "\t");
        $anuladas = [];
        $values_pack = [];
        foreach($data as $reg) {

            $operaciones = [];
            $operaciones['anulada']=0;
            if(strtoupper($reg['Estado_Poliza']) == 'ANULADA') {
                $operaciones['anulada']=true;
            }

            $operaciones['nro_suplemento']=$reg['Supl/End'];
            $operaciones['poliza']=$reg['Poliza/Contrato'];
            $operaciones['nropersona']=$reg['CdPer'];
            $operaciones['imp_pack']=$reg['Premio'];
            $operaciones['cuotas']=$reg['Cuotas'];
            $operaciones['nro_vinculante']=$reg['Nro_Vinculante'];
            $operaciones['expediente']=$reg['Expediente'];
            $values_pack[] = '('.implode(',',array_values($operaciones)).')';

        }

        $values_pack =  implode(',',$values_pack);
        if($values_pack) {
            $sql = "INSERT INTO list_opers (anulada,nro_suplemento,poliza,nropersona,imp_pack,cuotas,nro_vinculante,expediente) 
                    VALUES {$values_pack} ON DUPLICATE KEY UPDATE poliza=poliza";
            DB::select($sql);
        }
       
        /** Cuando procesamos por indoor tenemos los tres datos Poliza|Expediente|NroPersona */
        $sql = "UPDATE cuotas
        INNER JOIN list_opers on cuotas.POLIZA = list_opers.POLIZA
        SET 
            cuotas.NRO_VINCULANTE = list_opers.NRO_VINCULANTE,
            cuotas.IMP_PACK = round(list_opers.IMP_PACK/list_opers.cuotas,2),
            cuotas.ESTADO_POLIZA = IF(list_opers.ANULADA,'ANULADA',NULL)
        WHERE 
            cuotas.EXPEDIENTE = list_opers.EXPEDIENTE
        and cuotas.NROPERSONA = list_opers.NROPERSONA
        and cuotas.NRO_VINCULANTE is null
        and cuotas.grupo_cuota_id is null";

        DB::select($sql);

        /** Cuando la poliza se hace manual, solo se pone el numero de poliza */
        $sql = "UPDATE cuotas
        inner join list_opers on cuotas.POLIZA = list_opers.POLIZA
        SET 
            cuotas.NRO_VINCULANTE = list_opers.NRO_VINCULANTE,
            cuotas.EXPEDIENTE = list_opers.EXPEDIENTE,
            cuotas.NROPERSONA = list_opers.NROPERSONA,
            cuotas.IMP_PACK = round(list_opers.IMP_PACK/list_opers.cuotas,2),
            cuotas.ESTADO_POLIZA = IF(list_opers.ANULADA,'ANULADA',NULL)
        where 
            cuotas.NRO_VINCULANTE is null
        and cuotas.grupo_cuota_id is null";

        DB::select($sql);


        return ['ok' => true];
    }


    private function csv_to_array($filename='', $delimiter=',')
    {
        if(!file_exists($filename) || !is_readable($filename)) return [];

        $header = NULL;
        $data = [];
        if (($handle = fopen($filename, 'r')) !== FALSE)
        {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE)
            {
                if(count($row) < count($this->campos_requeridos)) continue;

                if(!$header){
                    foreach($row as $k=>$v) {
                        $row[$k] = str_replace(" ", "_", utf8_encode(trim($v)));
                    }
                    $header = $row;
                    if(array_diff($this->campos_requeridos, $header)) break;
                }
                else {
                    if(count($row) != count($header)) continue; //Por si viene un registro con error (ej Sedan)
                    $row = $this->clearData($row);
                    $combinado = array_combine($header, $row);
                    $temp = [];
                    // Solo me interesan las anuladas y las pack.
                    if(($combinado['Secc'] == '99' && $combinado['Supl/End'] == '0')
                        || (strtoupper($combinado['Estado_Poliza']) == 'ANULADA')) {
                            foreach($this->campos_requeridos as $v){
                                $temp[$v] = $combinado[$v];
                            }
                            $data[] = $temp;
                        }
                }
            }
            fclose($handle);
        }
        return $data;
    }


    private function clearData($row) {

        foreach($row as $k=>$value) {
            $v = preg_replace('/^\"/', '', $value);
            $v = preg_replace('/^\'/', '', $v);
            $v = preg_replace('/\"$/', '', $v);
            $v = preg_replace('/\'$/', '', $v);
            $v = utf8_encode($v);
            $v = trim($v);
            if($v=='') $v=null;
            $row[$k]=$v;
        }

        return $row;
    }
}


/*
'Emp','Secc','Producto','Poliza/Contrato','Supl/End','Poliza/Contrato','Propuesta','Nombre',
        'Doc/CUIT','CdPer','Emision','InicioVig','FinVig','Prima','Premio','Comision','Cuotas','Mon',
        'Suma_Asegurada','Secc_Hija','Poliza_Hija','Premio_Hija','Premio_Madre','Tipo_Suple',
        'Cuotas_Pagas','Total_Cobrado','Estado_Poliza'
 */


// foreach($data as $reg) {
//     $reg_pk = [];
//     foreach($this->campos_pk as $v){
//         $reg_pk[$v] = $reg[$v];
//     }
//     $ListOper = ListOper::updateOrCreate($reg_pk,$reg);
//     $ListOper->save();
// }
