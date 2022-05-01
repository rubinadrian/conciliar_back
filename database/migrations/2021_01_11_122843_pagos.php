<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Pagos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->string('ASEGURADO',100)->nullable();
            $table->string('OPERACION',15)->nullable();
            $table->string('ENTIDAD_ASEGURADORA',100)->nullable();
            $table->string('NRORECIBO',30)->nullable();
            $table->string('MONEDA',10)->nullable();
            $table->unsignedInteger('NROPERSONA')->nullable();
            $table->unsignedInteger('CIA')->nullable();
            $table->unsignedInteger('PROPUESTA')->nullable();
            $table->unsignedInteger('PRODUCTO')->nullable();
            $table->unsignedInteger('POLIZA')->nullable();
            $table->unsignedInteger('SUPLE')->nullable();
            $table->unsignedInteger('CUOTA')->nullable();
            $table->unsignedInteger('EXPEDIENTE')->nullable();
            $table->unsignedInteger('NROLOTE')->nullable();
            $table->unsignedInteger('NROTERM')->nullable();
            $table->unsignedInteger('MOVIMIENTO')->nullable();
            $table->double('COBRADO',15,2)->nullable();
            $table->double('COMISION',15,2)->nullable();
            $table->double('PRIMA',15,2)->nullable();
            $table->double('PREMIO',15,2)->nullable();
            $table->date('EMISION')->nullable();
            $table->date('VENCIMIENTO')->nullable();
            $table->date('FECCOBRO')->nullable();
            $table->time('HORCOBRO',0)->nullable();
            $table->string('ESTADO_POLIZA',20)->nullable();
            $table->unique(['NRORECIBO']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pagos');
    }
}

/**
 * NROPERSONA
 * ASEGURADO
 * OPERACION
 * CIA
 * ENTIDAD_ASEGURADORA
 * PROPUESTA
 * NRORECIBO
 * PRODUCTO
 * POLIZA
 * SUPLE
 * CUOTA
 * EXPEDIENTE
 * EMISION
 * MONEDA
 * COBRADO
 * VENCIMIENTO
 * FECCOBRO
 * HORCOBRO
 * NROLOTE
 * NROTERM
 * MOVIMIENTO
 * COMISION
 * PRIMA
 * PREMIO

 */
