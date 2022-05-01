<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Conciliacion extends Model
{
    public static $TIPOS_CONCILIAICONES = [
        'MANUAL'=> '1',
        'AUTOMATICA' => 2,
        'CRED_DEB' => 3,
        'AGRUPADAS_MES' => 4,
        'AUTO_MES_ANT' => 5,
        'AUTO_MES_SIG' => 6,
        'PACK' => 7,
        'PACK_MES_ANT' => 8,
        'PACK_MES_SIG' => 9,
        'AGRUPADAS_MES_NROPERSONA' => 10,
        'AGRUPADAS_MES_NROVINCULANTE' => 11,
    ];

    /** Este porcentaje es la diferencia permitida para poder conciliar. */
    public static $PORCENTAJE_PERMITIDO = 3;
    public static $IMPORTE_PERMITIDO_PACK = 100;
    public static $IMPORTE_PERMITIDO = 100;


    public $table = 'conciliaciones';

    public function grupocuotas()
    {
        return $this->hasOne('App\GrupoCuota');
    }

    public function grupopagos()
    {
        return $this->hasOne('App\GrupoPago');
    }
}
