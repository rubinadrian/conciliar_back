<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Cuota extends Model
{
    public function grupocuotas()
    {
        return $this->belongsTo('App\GrupoCuota', 'grupo_cuota_id');
    }
}
