<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{

    public function grupopagos()
    {
        return $this->belongsTo('App\GrupoPago', 'grupo_pago_id');
    }
}
