<?php

namespace App\Http\Controllers;

use App\ListOper;
use App\Pack;
use App\Anulada;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Log;

class ListOperBckController extends Controller
{
    private $campos_requeridos = ['Poliza/Contrato','Secc','Supl/End','CdPer','Premio','Cuotas','Estado_Poliza', 'Nro_Vinculante','Expediente'];

    public function uploadCSV()
    {
        $data = $this->csv_to_array($_FILES['file']['tmp_name'], "\t");
        $anuladas = [];
        $values_pack = [];
        foreach($data as $reg) {
            if(strtoupper($reg['Estado_Poliza']) == 'ANULADA') {
                $anuladas_temp['poliza']=$reg['Poliza/Contrato'];
                $anuladas_temp['expediente']=$reg['Expediente'];
                $values_anuladas[] = '('.implode(',',array_values($anuladas_temp)).')';
            }

            $pack_temp = [];
            if($reg['Secc'] == '99' && $reg['Supl/End'] == '0') {
                $pack_temp['poliza']=$reg['Poliza/Contrato'];
                $pack_temp['nropersona']=$reg['CdPer'];
                $pack_temp['imp_pack']=$reg['Premio'];
                $pack_temp['cuotas']=$reg['Cuotas'];
                $pack_temp['nro_vinculante']=$reg['Nro_Vinculante'];
                $pack_temp['expediente']=$reg['Expediente'];
                $values_pack[] = '('.implode(',',array_values($pack_temp)).')';
            }

        }

        $values_pack =  implode(',',$values_pack);
        if($values_pack) {
            $sql = "INSERT INTO packs (poliza,nropersona,imp_pack,cuotas,nro_vinculante,expediente) 
                    VALUES {$values_pack} ON DUPLICATE KEY UPDATE poliza=poliza";
            DB::select($sql);
        }
       
        $anuladas =  implode(',',$values_anuladas);
        if($anuladas) {
            $sql = "INSERT INTO anuladas (poliza,expediente) VALUES {$anuladas} ON DUPLICATE KEY UPDATE poliza=poliza";
            DB::select($sql);
        }

        /** Cuando procesamos por indoor tenemos los tres datos Poliza|Expediente|NroPersona */
        $sql = "update cuotas
        inner join packs on cuotas.POLIZA = packs.POLIZA
        set cuotas.NRO_VINCULANTE = packs.NRO_VINCULANTE,
        cuotas.IMP_PACK = round(packs.IMP_PACK/packs.cuotas,2)
        where 
            cuotas.EXPEDIENTE = packs.EXPEDIENTE
        and cuotas.NROPERSONA = packs.NROPERSONA
        and cuotas.NRO_VINCULANTE is null
        and cuotas.grupo_cuota_id is null";

        DB::select($sql);

        /** Cuando la poliza se hace manual, solo se pone el numero de poliza */
        $sql = "update cuotas
        inner join packs on cuotas.POLIZA = packs.POLIZA
        set cuotas.NRO_VINCULANTE = packs.NRO_VINCULANTE,
            cuotas.EXPEDIENTE = packs.EXPEDIENTE,
            cuotas.NROPERSONA = packs.NROPERSONA,
            cuotas.IMP_PACK = round(packs.IMP_PACK/packs.cuotas,2)
        where 
            cuotas.NRO_VINCULANTE is null
        and cuotas.grupo_cuota_id is null";

        DB::select($sql);

        $sql = "update cuotas
                inner join anuladas on cuotas.POLIZA = anuladas.POLIZA
                set estado_poliza = 'ANULADA'
                where cuotas.EXPEDIENTE = anuladas.EXPEDIENTE";
        
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
