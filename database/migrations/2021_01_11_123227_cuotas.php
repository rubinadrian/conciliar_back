<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Cuotas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cuotas', function (Blueprint $table) {
            $table->id();
            $table->string('POLIZA',50)->nullable();
            $table->double('IMPORTE', 15,2);
            $table->unsignedInteger('TIPOCOMP');
            $table->string('NROCOMPROBANTE', 20);
            $table->date('FECHAVTO');
            $table->unsignedInteger('CUOTAS');
            $table->unsignedInteger('NROCUOTA');
            $table->double('NROINTERNO',15,0);
            $table->unsignedInteger('NROPERSONA')->nullable();
            $table->string('ESTADO_POLIZA',20)->nullable();
            $table->double('IMP_PACK',15,2)->nullable();
            $table->timestamps();
            $table->unique(['NROINTERNO', 'NROCUOTA']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cuotas');
    }
}
