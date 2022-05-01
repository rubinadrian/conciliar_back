<?php

namespace App\Http\Controllers;

use App\Conciliacion;
use App\Pago;
use App\Cuota;
use App\GrupoCuota;
use App\GrupoPago;
use App\ListOper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DateTime;
use Illuminate\Support\Facades\Log;

class ConciliacionController extends Controller
{


    public function conciliar(Request $request) {
        $input = $request->only(['pagos', 'cuotas', 'conc_inicial']);

        $this->conciliarPorIds($input['pagos'],$input['cuotas'],$input['conc_inicial'],Conciliacion::$TIPOS_CONCILIAICONES['MANUAL']);

        return ['ok'=>true];
    }

    private function conciliarPorIds($ids_pagos,$ids_cuotas,$conc_inicial=false, $tipo) {
        if(count($ids_pagos)==0 && count($ids_cuotas)==0) return;

        $conciliacion = Conciliacion::create();
        $conciliacion->tipo = $tipo;
        $conciliacion->conc_inicial = $conc_inicial;
        $conciliacion->save();

        $pagos = Pago::find($ids_pagos);
        $cuotas = Cuota::find($ids_cuotas);

        // if(!$this->ctrlDifCuotasPagos($pagos, $cuotas)) {
        //     return false;
        // }


        if(count($ids_pagos)>0) {
            $gp_pagos = new GrupoPago;
            $conciliacion->grupopagos()->save($gp_pagos);
            $gp_pagos->pagos()->saveMany($pagos);
        }

        if(count($ids_cuotas)>0) {
            $gp_cuotas = new GrupoCuota;
            $conciliacion->grupocuotas()->save($gp_cuotas);
            $gp_cuotas->cuotas()->saveMany($cuotas);
        }
    }

    private function ctrlDifCuotasPagos($pagos, $cuotas) {
        $importe_pago = 0;
        foreach($pagos as $pago) {
            $importe_pago += $pago->COBRADO * 1;
        }     
        
        $importe_cuota = 0;
        foreach($cuotas as $cuota) {
            $importe_cuota += $cuota->IMPORTE * 1;
        }

        if(abs($importe_cuota - $importe_pago) > Conciliacion::$IMPORTE_PERMITIDO) {
            return false;
        }
    }

    public function conciliarDebitadaAcreditada() {
        // Buscamos los debitos, que en la 13017 o 13097 son generalmente para cancelar un movimiento.
        $debitos = Cuota::whereNUll('grupo_cuota_id')
                        ->where('IMPORTE','>','0')
                        ->get();
        // Con los debitos, buscamos si hay creditos con esos numeros de polizas
        $creditos = Cuota::whereNUll('grupo_cuota_id')
                            ->where('IMPORTE','<','0')
                            ->whereIn('POLIZA',$debitos->pluck('POLIZA'))
                            ->get();
        // ahora con los debitos y creditos vemos si alguno se cancela.
        $conciliados = [];
        $count = 0;
        if(count($debitos)>0 && count($creditos)>0) {
            foreach($debitos as $debito) {
                foreach ($creditos as $credito) {
                    if ($credito->POLIZA == $debito->POLIZA
                    && $credito->cuenta == $debito->cuenta
                    && abs($credito->IMPORTE + $debito->IMPORTE) <= Conciliacion::$IMPORTE_PERMITIDO
                    && abs(($credito->IMPORTE + $debito->IMPORTE)*100/$credito->IMPORTE) <= Conciliacion::$PORCENTAJE_PERMITIDO) {
                        if($this->conciliarDosCuotas($credito,$debito))  {
                            $count++;
                            $conciliados[] = ['Debito'=>$debito,'Credito'=>$credito];
                        }
                        break;
                    }
                }
            }
        }

        return ['ok'=>true, 'count'=>$count];
    }


    /** Concilia de forma automatica. Busca pagos contra creditos en la 13017 que coincidan en mes e importe */
    public function conciliarAuto($tipo_conciliacion = 0) {
        $date = new DateTime('now');
        $date->modify('last day of this month');
        $fecha_hasta = $date->format('Y-m-d');

        $query = Pago::select('pagos.id as pago_id','cuotas.id as cuota_id')
                        ->join('cuotas','cuotas.poliza','=','pagos.poliza')
                        ->where(DB::raw('abs((pagos.cobrado + cuotas.importe)*100/pagos.cobrado)'),'<',Conciliacion::$PORCENTAJE_PERMITIDO)
                        ->where('cuotas.fechavto','<=',$fecha_hasta)
                        ->whereRaw('pagos.cuota = cuotas.nrocuota')
                        ->whereNull('grupo_pago_id')
                        ->whereNUll('grupo_cuota_id')
                        ->where(function($query) {
                            $query->where(function($q) {
                                $q->where('cia', '1')
                                  ->where('cuenta', '13017');
                            });
                            $query->orWhere(function($q) {
                                $q->where('cia', '2')
                                  ->where('cuenta', '13097');
                            });
                        });

        if($tipo_conciliacion == 0) {
            $tipo = Conciliacion::$TIPOS_CONCILIAICONES['AUTOMATICA'];
            $query->whereRaw('YEAR(pagos.feccobro) = Year(cuotas.fechavto)');
            $query->whereRaw('MONTH(pagos.feccobro) = MONTH(cuotas.fechavto)');
        } else if($tipo_conciliacion == 1) {
            $tipo = Conciliacion::$TIPOS_CONCILIAICONES['AUTO_MES_ANT'];
            $query->whereRaw('YEAR(pagos.feccobro) = Year(DATE_ADD(cuotas.fechavto,INTERVAL -1 MONTH))');
            $query->whereRaw('MONTH(pagos.feccobro) = MONTH(DATE_ADD(cuotas.fechavto,INTERVAL -1 MONTH))');
        } else if($tipo_conciliacion == 2) {
            $tipo = Conciliacion::$TIPOS_CONCILIAICONES['AUTO_MES_SIG'];
            $query->whereRaw('YEAR(pagos.feccobro) = Year(DATE_ADD(cuotas.fechavto,INTERVAL 1 MONTH))');
            $query->whereRaw('MONTH(pagos.feccobro) = MONTH(DATE_ADD(cuotas.fechavto,INTERVAL 1 MONTH))');
        }
        DB::enableQueryLog();
        $paresIdsaConciliar = $query->get();
        $count = 0;
        
        // Log::info(DB::getQueryLog());
        // Log::info('Cantidad = ' . count($paresIdsaConciliar));

        foreach($paresIdsaConciliar as $parIds) {
            if($this->conciliarUnoAUno($parIds->cuota_id, $parIds->pago_id, $tipo)) $count++;
        }

        return ['ok'=>true, 'count'=>$count];
    }


    /** El numero de poliza en las pack dentro de Pagos vienen con un numero vinculante
     * Este numero lo asociamos con el listado de operaciones en las cuotas.
     * por tanto pagos.poliza = cuotas.nro_vinculante
     */
    public function conciliarPackAuto($tipo_conciliacion = 0) {
        $date = new DateTime('now');
        $date->modify('last day of this month');
        $fecha_hasta = $date->format('Y-m-d');

        $query = Pago::select('pagos.id as pago_id','cuotas.id as cuota_id')
                        ->join('cuotas','cuotas.nro_vinculante','=','pagos.poliza')
                        ->whereColumn('cuotas.nro_vinculante','pagos.poliza')
                        ->where(DB::raw('abs((pagos.cobrado + cuotas.importe)*100/pagos.cobrado)'),'<',Conciliacion::$PORCENTAJE_PERMITIDO)
                        ->where(DB::raw('abs(pagos.cobrado + cuotas.importe)'),'<',Conciliacion::$IMPORTE_PERMITIDO)
                        ->where('cuotas.fechavto','<=',$fecha_hasta)
                        ->whereNull('grupo_pago_id')
                        ->whereNUll('grupo_cuota_id')
                        ->whereRaw('pagos.cuota = cuotas.nrocuota')
                        ->where(function($query2) {
                            $query2->where(function($q) {
                                $q->where('cia', '1')
                                  ->where('cuenta', '13017');
                            });
                            $query2->orWhere(function($q) {
                                $q->where('cia', '2')
                                  ->where('cuenta', '13097');
                            });
                        });        
        if($tipo_conciliacion == 0) {
            $tipo = Conciliacion::$TIPOS_CONCILIAICONES['PACK'];
            $query->whereRaw('YEAR(pagos.feccobro) = Year(cuotas.fechavto)');
            $query->whereRaw('MONTH(pagos.feccobro) = MONTH(cuotas.fechavto)');
        } else if($tipo_conciliacion == 1) {
            $tipo = Conciliacion::$TIPOS_CONCILIAICONES['PACK_MES_ANT'];
            $query->whereRaw('YEAR(pagos.feccobro) = Year(DATE_ADD(cuotas.fechavto,INTERVAL -1 MONTH))');
            $query->whereRaw('MONTH(pagos.feccobro) = MONTH(DATE_ADD(cuotas.fechavto,INTERVAL -1 MONTH))');
        } else if($tipo_conciliacion == 2) {
            $tipo = Conciliacion::$TIPOS_CONCILIAICONES['PACK_MES_SIG'];
            $query->whereRaw('YEAR(`pagos`.`feccobro`) = Year(DATE_ADD(`cuotas`.`fechavto`,INTERVAL 1 MONTH))');
            $query->whereRaw('MONTH(`pagos`.`feccobro`) = MONTH(DATE_ADD(`cuotas`.`fechavto`,INTERVAL 1 MONTH))');
        } else {
            return [];
        }

        
        $paresIdsaConciliar = $query->get();


        foreach($paresIdsaConciliar as $parIds) {
            $this->conciliarUnoAUno($parIds->cuota_id, $parIds->pago_id, $tipo);
        }

        $this->corrigeConciliacionesPacks();
        
        return ['ok'=>true, 'count'=>count($paresIdsaConciliar)];
    }



    public function conciliarAutoAgrupandoMes() {
        $sql = "SELECT
                    a.month,
                    a.year,
                    a.poliza,
                    a.importe,
                    b.cobrado,
                    ROUND((importe + cobrado), 2) dif,
                    ROUND(((importe + cobrado) * 100 / cobrado), 2) dif_porc
                FROM
                    (
                    SELECT
                        YEAR(cuotas.fechavto) AS YEAR,
                        MONTH(cuotas.fechavto) AS MONTH,
                        cuotas.nrocuota,
                        cuotas.poliza AS poliza,
                        SUM(cuotas.IMPORTE) importe
                    FROM
                        cuotas
                    WHERE
                        cuotas.grupo_cuota_id is null
                    GROUP BY YEAR, MONTH,poliza,nrocuota
                ) a,
                (
                    SELECT
                        YEAR(pagos.feccobro) AS YEAR,
                        MONTH(pagos.feccobro) AS MONTH,
                        pagos.cuota,
                        pagos.poliza AS poliza,
                        SUM(pagos.COBRADO) cobrado
                    FROM
                        pagos
                    WHERE
                        pagos.grupo_pago_id is null
                    GROUP BY YEAR, MONTH,poliza,cuota
                ) b
                WHERE
                    a.poliza = b.poliza 
                    AND a.month = b.month 
                    AND a.year = b.year
                    and a.nrocuota = b.cuota
                    AND ABS(importe + cobrado) < ".Conciliacion::$IMPORTE_PERMITIDO." 
                    AND ABS((importe + cobrado) * 100 / cobrado) < ".Conciliacion::$PORCENTAJE_PERMITIDO;

        $regs = DB::select($sql);

        foreach($regs as $reg) {
            $pagos = Pago::whereRaw('YEAR(pagos.feccobro) = '.$reg->year)
                        ->whereRaw('MONTH(pagos.feccobro) = '.$reg->month)
                        ->whereNull('grupo_pago_id')
                        ->where('poliza', $reg->poliza)
                        ->get();

            $cuotas = Cuota::whereRaw('YEAR(cuotas.fechavto) = '.$reg->year)
                        ->whereRaw('MONTH(cuotas.fechavto) = '.$reg->month)
                        ->whereNull('grupo_cuota_id')
                        ->where('poliza', $reg->poliza)
                        ->get();
            $tipo = Conciliacion::$TIPOS_CONCILIAICONES['AGRUPADAS_MES'];
            $this->conciliarPorIds($pagos->pluck('id'),$cuotas->pluck('id'), false, $tipo);
        }

        return ['ok'=>true, 'count'=> count($regs)];
    }

    public function conciliarAutoAgrupandoMesNroPersona() {
        $sql = "SELECT
                    a.month,
                    a.year,
                    a.nropersona,
                    a.importe,
                    b.cobrado,
                    ROUND((importe + cobrado), 2) dif,
                    ROUND(((importe + cobrado) * 100 / cobrado), 2) dif_porc
                FROM
                    (
                    SELECT
                        YEAR(cuotas.fechavto) AS YEAR,
                        MONTH(cuotas.fechavto) AS MONTH,
                        cuotas.nrocuota,
                        cuotas.nropersona AS nropersona,
                        SUM(cuotas.IMPORTE) importe
                    FROM
                        cuotas
                    WHERE
                        cuotas.grupo_cuota_id is null
                    and cuotas.nropersona is not null
                    GROUP BY YEAR, MONTH, nropersona,nrocuota
                ) a,
                (
                    SELECT
                        YEAR(pagos.feccobro) AS YEAR,
                        MONTH(pagos.feccobro) AS MONTH,
                        pagos.nropersona AS nropersona,
                        pagos.cuota,
                        SUM(pagos.COBRADO) cobrado
                    FROM
                        pagos
                    WHERE
                        pagos.grupo_pago_id is null
                    and pagos.nropersona is not null
                    GROUP BY YEAR, MONTH, nropersona,cuota
                ) b
                WHERE
                    a.nropersona = b.nropersona 
                    AND a.month = b.month 
                    AND a.year = b.year
                    AND a.nrocuota = b.cuota
                    AND ABS(importe + cobrado) < ".Conciliacion::$IMPORTE_PERMITIDO." 
                    AND ABS((importe + cobrado) * 100 / cobrado) < ".Conciliacion::$PORCENTAJE_PERMITIDO;

        $regs = DB::select($sql);

        foreach($regs as $reg) {
            $pagos = Pago::whereRaw('YEAR(pagos.feccobro) = '.$reg->year)
                        ->whereRaw('MONTH(pagos.feccobro) = '.$reg->month)
                        ->whereNull('grupo_pago_id')
                        ->where('nropersona', $reg->nropersona)
                        ->get();

            $cuotas = Cuota::whereRaw('YEAR(cuotas.fechavto) = '.$reg->year)
                        ->whereRaw('MONTH(cuotas.fechavto) = '.$reg->month)
                        ->whereNull('grupo_cuota_id')
                        ->where('nropersona', $reg->nropersona)
                        ->get();
            $tipo = Conciliacion::$TIPOS_CONCILIAICONES['AGRUPADAS_MES_NROPERSONA'];
            $this->conciliarPorIds($pagos->pluck('id'),$cuotas->pluck('id'), false, $tipo);
        }

        return ['ok'=>true, 'count'=> count($regs)];
    }

    private function conciliarDosCuotas(Cuota $cuota1, Cuota $cuota2) {
        $tipo = Conciliacion::$TIPOS_CONCILIAICONES['CRED_DEB'];

        if(!$cuota1->grupo_cuota_id || !$cuota2->grupo_cuota_id ) { return false; }
        

        if(abs($cuota1->IMPORTE - $cuota2->IMPORTE) > Conciliacion::$IMPORTE_PERMITIDO) {
            return false;
        }

        $conciliacion = Conciliacion::create();
        $conciliacion->tipo = $tipo;
        $conciliacion->save();

        $gp_cuotas = new GrupoCuota;
        $conciliacion->grupocuotas()->save($gp_cuotas);
        $gp_cuotas->cuotas()->saveMany([$cuota1, $cuota2]);

        return true;
    }

    private function conciliarUnoAUno($cuota_id, $pago_id, $tipo_conciliacion = 0) {
        if(!$pago = Pago::where('id',$pago_id)->whereNull('grupo_pago_id')->first()) return false;
        if(!$cuota = Cuota::where('id',$cuota_id)->whereNull('grupo_cuota_id')->first()) return false;
        if(abs($pago->COBRADO + $cuota->IMPORTE) > Conciliacion::$IMPORTE_PERMITIDO) {
            return false;
        }

        if($pago && $cuota) {
            $conciliacion = Conciliacion::create();
            $conciliacion->tipo = $tipo_conciliacion;
            $conciliacion->save();
    
            $gp_pagos = new GrupoPago;
            $conciliacion->grupopagos()->save($gp_pagos);
            $gp_pagos->pagos()->save($pago);
    
            $gp_cuotas = new GrupoCuota;
            $conciliacion->grupocuotas()->save($gp_cuotas);
            $gp_cuotas->cuotas()->save($cuota);

            return true;
        }
        return false;        
    }

   public function getConciliacion($conciliacion_id) {
        $conc = Conciliacion::find($conciliacion_id);
        $cuotas = [];
        $pagos = [];
        if($conc) {
            if($conc->grupopagos) {
                $pagos = $conc->grupopagos->pagos()->get();
            }
            if($conc->grupocuotas){
                $cuotas = $conc->grupocuotas->cuotas()->get();
            }
        }
        return ['cuotas'=>$cuotas,'pagos'=>$pagos];
    }


    public function delConciliacion($conciliacion_id) {
        $conc = Conciliacion::find($conciliacion_id);

        if($conc) {
            if($conc->grupocuotas){
                foreach($conc->grupocuotas->cuotas as $cuota) {
                    $cuota->grupo_cuota_id = null;
                    $cuota->save();
                }
            }

            if($conc->grupopagos){
                foreach($conc->grupopagos->pagos as $pago) {
                    $pago->grupo_pago_id = null;
                    $pago->save();
                }
            }

            $conc->grupocuotas()->delete();
            $conc->grupopagos()->delete();
            $conc->delete();
        }

        return ['ok'=>true];
    }

    
    public function getPagosConciliados($from, $to, $initial = false) {
        $date_from = DateTime::createFromFormat('Ymd', $from.'01');
        $date_to = DateTime::createFromFormat('Ymd', $to.'01');
        $date_to->modify('last day of this month');

        $sql_dif = $this->getSqlConciliadas();

        $query = Pago::select(DB::raw('pagos.*,comentarios.comentario, grupo_pagos.conciliacion_id, tdif.DIF'))
        ->leftJoin('comentarios', 'comentarios.POLIZA', '=', 'pagos.POLIZA')
        ->join('grupo_pagos', 'pagos.grupo_pago_id', '=', 'grupo_pagos.id')
        ->join('conciliaciones', 'conciliaciones.id','=', 'grupo_pagos.conciliacion_id')
        ->leftJoin(DB::raw($sql_dif),function($q){ $q->on('grupo_pagos.conciliacion_id', '=', 'tdif.conciliacion_id');})
        ->whereIn('cia',['1','2'])
        ->where('feccobro', '>=', $date_from->format('Y-m-d'))
        ->where('feccobro', '<=', $date_to->format('Y-m-d'))
        ->orderBy('feccobro');

        if($initial) {
            $query = $query->where('conciliaciones.conc_inicial', true);
        } else {
            $query = $query->where('conciliaciones.conc_inicial', false);
        }

        return $query->get();
    }

    public function getCuotasConciliadas($from, $to, $initial = false) {
        $date_from = DateTime::createFromFormat('Ymd', $from.'01');
        $date_to = DateTime::createFromFormat('Ymd', $to.'01');
        $date_to->modify('last day of this month');

        $sql_dif = $this->getSqlConciliadas();

        $query = Cuota::select(DB::raw('cuotas.*,comentarios.comentario, grupo_cuotas.conciliacion_id, tdif.DIF'))
        ->leftJoin('comentarios', 'comentarios.POLIZA', '=', 'cuotas.POLIZA')
        ->join('grupo_cuotas', 'cuotas.grupo_cuota_id', '=', 'grupo_cuotas.id')
        ->join('conciliaciones', 'conciliaciones.id','=', 'grupo_cuotas.conciliacion_id')
        ->leftJoin(DB::raw($sql_dif),function($q){ $q->on('grupo_cuotas.conciliacion_id', '=', 'tdif.conciliacion_id');})
        ->where('fechavto', '>=', $date_from->format('Y-m-d'))
        ->where('fechavto', '<=', $date_to->format('Y-m-d'))
        ->orderBy('fechavto');

        if($initial) {
            $query = $query->where('conciliaciones.conc_inicial', true);
        } else {
            $query = $query->where('conciliaciones.conc_inicial', false);
        }

        return $query->get();
    }


    private function getSqlConciliadas() {
        $sql = "
            (SELECT
                t1.conciliacion_id,
                sum(importe) as DIF
            FROM
                (
                    (
                        select
                            grupo_pagos.conciliacion_id as conciliacion_id,
                            sum(cobrado) as importe
                        from
                            pagos
                            left join grupo_pagos on grupo_pagos.id = pagos.grupo_pago_id
                            left join conciliaciones on conciliaciones.id = grupo_pagos.conciliacion_id
                        where
                            grupo_pago_id is not null
                        group by
                            conciliacion_id,
                            grupo_pago_id
                    )
                    union
                    (
                        select
                            grupo_cuotas.conciliacion_id as conciliacion_id,
                            sum(importe) as importe
                        from
                            cuotas
                            left join grupo_cuotas on grupo_cuotas.id = cuotas.grupo_cuota_id
                            left join conciliaciones on conciliaciones.id = grupo_cuotas.conciliacion_id
                        where
                            grupo_cuota_id is not null
                        group by
                            conciliacion_id,
                            grupo_cuota_id
                    )
                ) as t1
            group by conciliacion_id) as tdif
        ";
        return $sql;
    }

/**
 * http://back.conciliar.coopunion.com.ar/public/index.php/api/conciliacion/limpiar_mal_hechas
 */
    public function limpiarConciliacionesMalHechas() {
        $count = 0;
        $sql = "
        select conciliaciones.id as id from conciliaciones, (
            select conciliacion_id from grupo_cuotas, (
            select grupo_cuota_id, count(*) as count from cuotas
            group by grupo_cuota_id
            having count = 1) c
            where c.grupo_cuota_id = grupo_cuotas.id
            ) as d 
            where id not in (select conciliacion_id from grupo_pagos)
            and d.conciliacion_id = id";
        $conciliaciones = DB::select($sql);
        
        foreach($conciliaciones as $conciliacion) {
            $this->delConciliacion($conciliacion->id);
            $count++;
        }

        $sql = "
        select conciliaciones.id as id from conciliaciones, (
            select conciliacion_id from grupo_pagos, (
            select grupo_pago_id, count(*) as count from pagos
            group by grupo_pago_id
            having count = 1) c
            where c.grupo_pago_id = grupo_pagos.id
            ) as d 
            where id not in (select conciliacion_id from grupo_cuotas)
            and d.conciliacion_id = id
        ";

        $conciliaciones = DB::select($sql);
        
        foreach($conciliaciones as $conciliacion) {
            $this->delConciliacion($conciliacion->id);
            $count++;
        }


        $sql = "
        select * from grupo_cuotas
        where id not in (select grupo_cuota_id from cuotas where grupo_cuota_id is not null)
        ";

        $grupos = DB::select($sql);
        
        foreach($grupos as $grupo) {
            $this->delConciliacion($grupo->conciliacion_id);
            $count++;
        }

        $sql = "
        select * from grupo_pagos 
        where id not in (select grupo_pago_id from pagos where grupo_pago_id is not null)
        ";

        $grupos = DB::select($sql);
        
        foreach($grupos as $grupo) {
            $this->delConciliacion($grupo->conciliacion_id);
            $count++;
        }

        return ['ok'=>true, 'count'=> $count];
    }

    /** Las pack anteriormente se hacian en dos debitos, uno para auto en la 13017 y otra para vida 13097 
     * Cuando haciamos conciliaciones sin traer las cuotas de la 13097, las conciliavamos con la diferencia de vida
     * que ronda en los $75.
     * Ahora que traemos las vidas de la 13097, tenemos que asociar estas cuotas a conciliaciones ya existentes.
    */
    private function corrigeConciliacionesPacks() {
        $sql = "UPDATE cuotas as c1 
                INNER JOIN (SELECT sum(cuenta) as control, 
                    nro_vinculante, 
                    MONTH(cuotas.fechavto) AS MONTH, 
                    YEAR(cuotas.fechavto) AS YEAR, 
                    sum(IMPORTE) as imptotal, 
                    sum(IMP_PACK)/2 as imptotal_pack, 
                    sum(abs(IMPORTE) - abs(IMP_PACK/2)) as dif, 
                    sum(grupo_cuota_id) grupo_cuota_id 
                FROM `cuotas` 
                WHERE nro_vinculante is not null 
                AND nro_vinculante <> 0 
                AND (if((cuenta = 13017 AND grupo_cuota_id is not null),1,0) = 1 
                     or if((cuenta = 13097 AND grupo_cuota_id is null),1,0) = 1) 
                GROUP BY NRO_VINCULANTE, 
                MONTH(cuotas.fechavto), 
                YEAR(cuotas.fechavto) 
                HAVING abs(dif) <= 10 
                AND control = 26114) AS q1 on q1.nro_vinculante = c1.NRO_VINCULANTE 
                SET c1.grupo_cuota_id = q1.grupo_cuota_id
                WHERE MONTH(c1.fechavto) = q1.MONTH 
                AND YEAR(c1.fechavto) = q1.YEAR
        ";

        DB::select($sql);
    }

}