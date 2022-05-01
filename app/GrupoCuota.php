<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GrupoCuota extends Model
{
    public function conciliacion()
    {
        return $this->belongsTo('App\Conciliacion');
    }

    public function cuotas()
    {
        return $this->hasMany('App\Cuota');
    }
}
