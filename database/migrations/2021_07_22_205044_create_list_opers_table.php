<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateListOpersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('list_opers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('NROPERSONA')->nullable();
            $table->string('POLIZA',30)->nullable();
            $table->unsignedInteger('NRO_SUPLEMENTO')->default(0);
            $table->double('IMP_PACK',15,2)->nullable();
            $table->unsignedInteger('CUOTAS')->default('1');
            $table->string('NRO_VINCULANTE',50)->nullable();
            $table->string('EXPEDIENTE',50)->nullable();
            $table->boolean('ANULADA')->default(false)->nullable();
            $table->timestamps();
            $table->unique(['NROPERSONA', 'POLIZA','NRO_SUPLEMENTO']);// Supongo que una persona no puede tener dos polizas con el mismo numero
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('list_opers');
    }
}
