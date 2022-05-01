<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GrupoPago extends Model
{
    public function conciliacion()
    {
        return $this->belongsTo('App\Conciliacion');
    }

    public function pagos()
    {
        return $this->hasMany('App\Pago');
    }
}
